"""
Printer Service for Windows GDI and TSPL RAW Spooler Integration.
Handles printer discovery, hardware protocol detection (TSPL vs GDI),
and document spooling for single and multi-product batch sequences.
Distinguishes spooler submission from physical verification.
"""

import os
import sys
from typing import Any, Dict, List, Optional, Tuple, Union
from PIL import Image

# Twin-Section Media Configuration (Xprinter XP-233B 38x25mm twin-section roll)
TWIN_SECTION_MODE: bool = os.environ.get("TWIN_SECTION_MODE", "1").strip().lower() not in ("0", "false", "no", "off")
TWIN_CANVAS_WIDTH_DOTS: int = 304
TWIN_CANVAS_HEIGHT_DOTS: int = 200
TOP_OFFSET_DOTS: int = 0
SECOND_OFFSET_DOTS: int = 104
TWIN_ALLOWED_HEIGHTS_MM: Tuple[int, ...] = (12, 13)

try:
    import win32print
    import win32ui
    import win32con
    import win32gui
    import win32file
    from PIL import ImageWin
    HAS_WIN32 = True
except ImportError:
    HAS_WIN32 = False


class PrinterNotFoundError(Exception):
    """Raised when the specified printer name does not exist on the system."""
    pass


class PrinterSpoolerError(Exception):
    """Raised when Windows Spooler / Driver fails during printing."""
    pass


class PrinterService:
    """
    Manages interaction with Windows printer spooler via RAW TSPL or GDI Device Context.
    Supports atomic multi-product batch printing without blank label insertions.
    """

    TSPL_KEYWORDS = [
        "xprinter",
        "xp-",
        "tsc",
        "gprinter",
        "barcode",
        "label",
        "sticker",
    ]

    TWIN_SECTION_MODE: bool = TWIN_SECTION_MODE
    TWIN_CANVAS_WIDTH_DOTS: int = TWIN_CANVAS_WIDTH_DOTS
    TWIN_CANVAS_HEIGHT_DOTS: int = TWIN_CANVAS_HEIGHT_DOTS
    TOP_OFFSET_DOTS: int = TOP_OFFSET_DOTS
    SECOND_OFFSET_DOTS: int = SECOND_OFFSET_DOTS
    TWIN_ALLOWED_HEIGHTS_MM: Tuple[int, ...] = TWIN_ALLOWED_HEIGHTS_MM

    @staticmethod
    def get_available_printers() -> List[str]:
        """Enumerate all local and network printers configured on this Windows machine."""
        if not HAS_WIN32:
            return []

        try:
            flags = win32print.PRINTER_ENUM_LOCAL | win32print.PRINTER_ENUM_CONNECTIONS
            printers = win32print.EnumPrinters(flags)
            return [p[2] for p in printers]
        except Exception as e:
            sys.stderr.write(f"Warning: Failed to enumerate printers: {e}\n")
            return []

    @classmethod
    def resolve_printer_name(cls, requested_name: Optional[str] = None) -> str:
        """
        Intelligently resolves target Windows printer against installed printers.
        Matches exact, case-insensitive, fuzzy aliases (XP-80C, POS-80, XP80),
        network share paths, or falls back to Windows default printer.
        """
        if not HAS_WIN32:
            return requested_name or "XP-80C"

        printers = cls.get_available_printers()
        if not printers:
            return requested_name or "XP-80C"

        req = (requested_name or "").strip()
        if not req:
            try:
                return win32print.GetDefaultPrinter() or printers[0]
            except Exception:
                return printers[0]

        # 0. UNC Network Share Check (e.g. \\192.168.0.41\XP-80C or //192.168.0.41/XP-80C)
        # Preserve UNC path intact without stripping host/IP or falling back to local printers!
        if req.startswith(r"\\") or req.startswith("//"):
            return req.replace("/", "\\")

        # 1. Exact match
        if req in printers:
            return req

        req_lower = req.lower()

        # 2. Case-insensitive match
        for p in printers:
            if p.lower() == req_lower:
                return p

        # 3. Handle network share relative names only if not UNC (e.g. DOMAIN\Printer)
        if "\\" in req:
            share_part = req.split("\\")[-1].lower()
            for p in printers:
                if share_part in p.lower():
                    return p

        # 4. If looking for 80mm thermal receipt printer
        thermal_keywords = ["xp-80", "pos-80", "xp80", "pos80", "80c", "receipt"]
        if any(k in req_lower for k in thermal_keywords):
            for p in printers:
                p_l = p.lower()
                if any(k in p_l for k in thermal_keywords):
                    return p

        # 5. If looking for barcode label printer
        label_keywords = ["233", "xp-233", "barcode", "label", "sticker"]
        if any(k in req_lower for k in label_keywords):
            for p in printers:
                p_l = p.lower()
                if any(k in p_l for k in label_keywords):
                    return p

        # 6. If looking for office/doc printer
        doc_keywords = ["toshiba", "laser", "office", "generic printer"]
        if any(k in req_lower for k in doc_keywords):
            for p in printers:
                p_l = p.lower()
                if any(k in p_l for k in doc_keywords):
                    return p

        # 7. Fallback to Windows default printer if available
        try:
            default_p = win32print.GetDefaultPrinter()
            if default_p:
                return default_p
        except Exception:
            pass

        return req

    @classmethod
    def _ensure_network_share_access(cls, printer_path: str) -> None:
        """
        Ensure network credentials or connection to UNC share host if configured.
        Supports environment variables:
        - THERMAL_PRINTER_USER / PRINTER_NETWORK_USER
        - THERMAL_PRINTER_PASSWORD / PRINTER_NETWORK_PASSWORD
        """
        user = os.environ.get("THERMAL_PRINTER_USER") or os.environ.get("PRINTER_NETWORK_USER")
        pwd = os.environ.get("THERMAL_PRINTER_PASSWORD") or os.environ.get("PRINTER_NETWORK_PASSWORD") or ""

        norm_path = printer_path.replace("/", "\\")
        parts = [p for p in norm_path.split("\\") if p]
        if not parts:
            return
        host = parts[0]
        ipc_share = f"\\\\{host}\\IPC$"

        if user:
            if HAS_WIN32:
                try:
                    import win32wnet, win32netcon
                    win32wnet.WNetAddConnection2(
                        win32netcon.RESOURCETYPE_ANY,
                        None,
                        ipc_share,
                        None,
                        user,
                        pwd,
                        0
                    )
                except Exception as e_wnet:
                    sys.stderr.write(f"[printer_service] WNetAddConnection2 notice: {e_wnet}\n")
            try:
                import subprocess
                cmd = ["net", "use", ipc_share, pwd, f"/user:{user}"]
                subprocess.run(cmd, capture_output=True, timeout=5)
            except Exception as e:
                sys.stderr.write(f"[printer_service] Warning: net use authentication attempt: {e}\n")

    @classmethod
    def validate_printer(cls, printer_name: str) -> None:
        """Verify that the requested printer exists in Windows devices or is an accessible network path."""
        if not HAS_WIN32:
            return

        # If it's a UNC network share printer path (e.g. \\192.168.0.41\XP-80C)
        if printer_name.startswith(r"\\") or printer_name.startswith("//"):
            cls._ensure_network_share_access(printer_name)
            return

        printers = cls.get_available_printers()
        if not printers:
            return

        resolved = cls.resolve_printer_name(printer_name)
        if resolved in printers:
            return

        available_list = ", ".join(f"'{p}'" for p in printers)
        raise PrinterNotFoundError(
            f"Printer '{printer_name}' was not found on this system. Available printers: [{available_list}]."
        )

    @classmethod
    def is_tspl_compatible(cls, printer_name: str) -> bool:
        """
        Detect whether the printer uses TSPL (TSC) command language.
        Thermal label printers like Xprinter XP-233B accept direct TSPL in RAW mode,
        bypassing Windows GDI page size limitations and driver feed quirks.
        """
        name_lower = printer_name.lower()
        if any(keyword in name_lower for keyword in cls.TSPL_KEYWORDS):
            # XP-80 / POS-80 receipt printers are ESC/POS, not TSPL
            if "xp-80" in name_lower or "pos-80" in name_lower or "xp80" in name_lower or "80c" in name_lower:
                return False
            return True

        if HAS_WIN32:
            try:
                hprinter = win32print.OpenPrinter(printer_name)
                try:
                    info = win32print.GetPrinter(hprinter, 2)
                    driver_lower = str(info.get("pDriverName", "")).lower()
                    if any(keyword in driver_lower for keyword in cls.TSPL_KEYWORDS):
                        if "xp-80" in driver_lower or "pos-80" in driver_lower or "xp80" in driver_lower or "80c" in driver_lower:
                            return False
                        return True
                finally:
                    win32print.ClosePrinter(hprinter)
            except Exception:
                pass

        return False

    @classmethod
    def is_escpos_compatible(cls, printer_name: str) -> bool:
        """
        Detect whether the printer uses ESC/POS command language (thermal receipt printers).
        Matches Xprinter XP-80C, POS-80, Epson TM-T20, and generic receipt printers,
        or any network share path targeting an 80mm/58mm thermal receipt printer.
        """
        name_lower = (printer_name or "").lower()
        receipt_keywords = [
            "xp-80", "pos-80", "xp80", "pos80", "80c", "receipt", "thermal",
            "epson tm", "tm-t", "rp80", "xp-58", "pos-58", "58mm", "80mm"
        ]
        return any(keyword in name_lower for keyword in receipt_keywords)

    @classmethod
    def build_batch_tspl_payload(
        cls,
        items: List[Dict[str, Any]],
        default_width_mm: float = 38.0,
        default_height_mm: float = 13.0,
        gap_mm: float = 2.0,
        direction: int = 1,
        include_direction: bool = True,
    ) -> Tuple[bytes, List[Dict[str, Any]]]:
        """
        Convert a sequence of product label items into a single, continuous TSPL byte stream.

        Protocol Rules for Multi-Product Sequences:
        1. Media setup (SIZE, GAP, DIRECTION, REFERENCE) is emitted strictly ONCE at job start.
           Re-issuing SIZE or GAP between labels causes printer firmware to recalibrate paper length,
           which feeds an extra blank label.
        2. For each unique product image:
           - CLS clears the printer's RAM buffer.
           - BITMAP loads the monochrome bitmap at (0, 0).
           - PRINT 1,{qty} commands the hardware to print exactly {qty} copies of THIS image.
        3. No page-boundary or driver-reset commands are inserted between products.

        Args:
            include_direction: When True (default / production), emits the DIRECTION command in
                the job header. Set to False ONLY for controlled diagnostic testing to determine
                whether DIRECTION causes a blank-label calibration feed on this firmware.
                Does not affect any other command (CLS, SIZE, GAP, BITMAP, PRINT, REFERENCE).

        Returns:
            Tuple of (tspl_bytes, items_diagnostic_summary)
        """
        if not items:
            raise ValueError("No items provided for TSPL batch payload.")

        # Determine nominal dimensions from first item or defaults
        first_img = items[0]["image"]
        width_px_first, height_px_first = first_img.size
        nominal_width_mm = round((width_px_first / 203.0) * 25.4, 1) or default_width_mm
        nominal_height_mm = round((height_px_first / 203.0) * 25.4, 1) or default_height_mm

        # 1. Job Header (emitted ONCE at the start of the job)
        # DIAGNOSTIC NOTE: include_direction=False omits DIRECTION to test H2 hypothesis.
        direction_line = f"DIRECTION {direction}\r\n" if include_direction else ""
        job_header = (
            f"SIZE {nominal_width_mm:g} mm, {nominal_height_mm:g} mm\r\n"
            f"GAP {gap_mm:g} mm, 0 mm\r\n"
            f"{direction_line}"
            f"REFERENCE 0,0\r\n"
        ).encode("ascii")

        sys.stderr.write(
            f"[TSPL-HEADER] include_direction={include_direction} | "
            f"SIZE {nominal_width_mm:g}x{nominal_height_mm:g} mm | "
            f"GAP {gap_mm:g} mm | "
            f"{'DIRECTION ' + str(direction) if include_direction else 'DIRECTION omitted (diagnostic)'}\n"
        )
        sys.stderr.flush()

        payload = bytearray(job_header)
        items_summary = []

        # 2. Iterate through each product item
        for idx, item in enumerate(items):
            img: Image.Image = item["image"]
            qty = max(1, min(500, int(item.get("copies") or item.get("quantity") or 1)))

            width_px, height_px = img.size
            width_mm = round((width_px / 203.0) * 25.4, 1)
            height_mm = round((height_px / 203.0) * 25.4, 1)
            dpi = int(item.get("dpi") or 203)

            # TSPL BITMAP requires width in bytes (each byte = 8 pixels, MSB first)
            width_bytes = (width_px + 7) // 8
            padded_width_px = width_bytes * 8

            # Ensure pure monochrome 1-bit bitmap padded with white (1s) to byte boundary
            gray_img = img.convert("L")
            bw_img = gray_img.point(lambda p: 255 if p > 128 else 0, mode="1")

            padded_img = Image.new("1", (padded_width_px, height_px), 1)
            padded_img.paste(bw_img, (0, 0))
            raw_bitmap = padded_img.tobytes()

            expected_len = width_bytes * height_px
            if len(raw_bitmap) != expected_len:
                raise ValueError(
                    f"Bitmap byte length mismatch for item {idx + 1}: "
                    f"got {len(raw_bitmap)} bytes, expected {expected_len} ({width_bytes} x {height_px})"
                )

            # TSPL per-product command block: CLS -> BITMAP -> PRINT 1,{qty}
            cmd_header = f"CLS\r\nBITMAP 0,0,{width_bytes},{height_px},0,".encode("ascii")
            cmd_footer = f"\r\nPRINT 1,{qty}\r\n".encode("ascii")

            payload.extend(cmd_header)
            payload.extend(raw_bitmap)
            payload.extend(cmd_footer)

            # Detailed diagnostic metadata (excluding raw binary data)
            item_diag = {
                "item_index": idx + 1,
                "product_id": str(item.get("product_id") or item.get("id") or item.get("sku") or f"item_{idx + 1}"),
                "product_name": str(item.get("product_name") or ""),
                "sku": str(item.get("sku") or ""),
                "requested_quantity": qty,
                "image_width_px": width_px,
                "image_height_px": height_px,
                "image_width_mm": width_mm,
                "image_height_mm": height_mm,
                "printer_dpi": dpi,
                "bitmap_width_bytes": width_bytes,
                "bitmap_payload_length": len(raw_bitmap),
                "configured_label_height_mm": height_mm,
                "configured_gap_mm": gap_mm,
                "tspl_command": f"CLS -> BITMAP 0,0,{width_bytes},{height_px},0,[{len(raw_bitmap)} bytes] -> PRINT 1,{qty}",
            }
            items_summary.append(item_diag)

            # Log to stderr cleanly without binary data
            sys.stderr.write(
                f"[TSPL-BATCH] [{idx + 1}/{len(items)}] ID={item_diag['product_id']} | "
                f"Name='{item_diag['product_name']}' | Qty={qty} | "
                f"Size={width_px}x{height_px}px ({width_mm}x{height_mm}mm, {dpi}DPI) | "
                f"Bitmap={width_bytes}B x {height_px}dots = {len(raw_bitmap)}B | "
                f"Gap={gap_mm}mm | Cmd: {item_diag['tspl_command']}\n"
            )
            sys.stderr.flush()

        return bytes(payload), items_summary

    @staticmethod
    def _image_to_monochrome_bytes(img: Image.Image) -> Tuple[int, int, bytes]:
        """Convert a PIL Image to 1-bit monochrome bytes matching TSPL BITMAP expectations."""
        width_px, height_px = img.size
        width_bytes = (width_px + 7) // 8
        padded_width_px = width_bytes * 8

        gray_img = img.convert("L")
        bw_img = gray_img.point(lambda p: 255 if p > 128 else 0, mode="1")

        padded_img = Image.new("1", (padded_width_px, height_px), 1)
        padded_img.paste(bw_img, (0, 0))
        raw_bitmap = padded_img.tobytes()
        return width_bytes, height_px, raw_bitmap

    @classmethod
    def is_twin_section_target(
        cls,
        printer_name: str,
        width_mm: float,
        height_mm: float
    ) -> bool:
        """
        Check if current print job targets the Xprinter XP-233B 38x25mm twin-section roll.
        Gating conditions:
        1. TWIN_SECTION_MODE is ON.
        2. printer_name == 'Xprinter XP-233B'
        3. width_mm rounds to 38
        4. height_mm rounds to one of TWIN_ALLOWED_HEIGHTS_MM (e.g. 12, 13)
        """
        env_val = os.environ.get("TWIN_SECTION_MODE", "1").strip().lower()
        active = (env_val not in ("0", "false", "no", "off")) and getattr(cls, "TWIN_SECTION_MODE", True)
        if not active:
            return False
        if (printer_name or "").strip() != "Xprinter XP-233B":
            return False
        if int(round(width_mm)) != 38:
            return False
        if int(round(height_mm)) not in cls.TWIN_ALLOWED_HEIGHTS_MM:
            return False
        return True

    @classmethod
    def build_twin_batch_tspl_payload(
        cls,
        items: List[Dict[str, Any]],
        gap_mm: float = 2.0,
        direction: int = 0,
        include_direction: bool = True,
        top_offset_dots: Optional[int] = None,
        second_offset_dots: Optional[int] = None,
    ) -> Tuple[bytes, List[Dict[str, Any]]]:
        """
        Build TSPL batch payload for 38x25mm twin-section roll (two ~12mm labels per 25mm carrier spot).
        Composes 304x200 canvas with top and bottom sections, then rotates 180 degrees before BITMAP.
        """
        if not items:
            raise ValueError("No items provided for twin TSPL batch payload.")

        top_offset = cls.TOP_OFFSET_DOTS if top_offset_dots is None else top_offset_dots
        second_offset = cls.SECOND_OFFSET_DOTS if second_offset_dots is None else second_offset_dots

        # In twin mode, always emit DIRECTION 0 (verified physical combination: DIRECTION 0 + 180° rotation)
        direction_line = "DIRECTION 0\r\n" if include_direction else ""
        job_header = (
            f"SIZE 38 mm, 25 mm\r\n"
            f"GAP {gap_mm:g} mm, 0 mm\r\n"
            f"{direction_line}"
            f"REFERENCE 0,0\r\n"
        ).encode("ascii")

        sys.stderr.write(
            f"[TSPL-TWIN-HEADER] include_direction={include_direction} | "
            f"SIZE 38x25 mm | GAP {gap_mm:g} mm | "
            f"{'DIRECTION 0' if include_direction else 'DIRECTION omitted'} | "
            f"Offsets: Top={top_offset}, Second={second_offset}\n"
        )
        sys.stderr.flush()

        label_units = []
        for src_idx, item in enumerate(items):
            img = item["image"]
            qty = max(1, min(500, int(item.get("copies") or item.get("quantity") or 1)))
            p_id = str(item.get("product_id") or item.get("id") or item.get("sku") or "")
            p_name = str(item.get("product_name") or "")
            p_sku = str(item.get("sku") or "")
            for _ in range(qty):
                label_units.append({
                    "source_item_index": src_idx,
                    "image": img,
                    "product_id": p_id,
                    "product_name": p_name,
                    "sku": p_sku,
                    "item_ref": item,
                })

        pairs = []
        i = 0
        while i < len(label_units):
            top_unit = label_units[i]
            bot_unit = label_units[i + 1] if (i + 1 < len(label_units)) else None
            pairs.append((top_unit, bot_unit))
            i += 2

        grouped_runs = []
        for top_unit, bot_unit in pairs:
            top_key = top_unit["source_item_index"]
            bot_key = bot_unit["source_item_index"] if bot_unit else None
            key = (top_key, bot_key)

            if grouped_runs and grouped_runs[-1]["key"] == key:
                grouped_runs[-1]["count"] += 1
            else:
                grouped_runs.append({
                    "key": key,
                    "top_unit": top_unit,
                    "bot_unit": bot_unit,
                    "count": 1,
                })

        payload = bytearray(job_header)
        for run_idx, run in enumerate(grouped_runs):
            top_img = run["top_unit"]["image"]
            bot_img = run["bot_unit"]["image"] if run["bot_unit"] else None
            count = run["count"]

            canvas = Image.new("RGB", (cls.TWIN_CANVAS_WIDTH_DOTS, cls.TWIN_CANVAS_HEIGHT_DOTS), "white")
            canvas.paste(top_img, (0, top_offset))
            if bot_img is not None:
                canvas.paste(bot_img, (0, second_offset))

            canvas_rotated = canvas.rotate(180)
            width_bytes, height_dots, raw_bitmap = cls._image_to_monochrome_bytes(canvas_rotated)

            expected_len = width_bytes * height_dots
            if len(raw_bitmap) != expected_len:
                raise ValueError(
                    f"Twin bitmap byte length mismatch: got {len(raw_bitmap)} bytes, expected {expected_len}"
                )

            cmd_header = f"CLS\r\nBITMAP 0,0,{width_bytes},{height_dots},0,".encode("ascii")
            cmd_footer = f"\r\nPRINT 1,{count}\r\n".encode("ascii")

            payload.extend(cmd_header)
            payload.extend(raw_bitmap)
            payload.extend(cmd_footer)

            sys.stderr.write(
                f"[TSPL-TWIN-BATCH] Run [{run_idx + 1}/{len(grouped_runs)}] "
                f"Top={run['top_unit']['product_id']} | "
                f"Bot={run['bot_unit']['product_id'] if run['bot_unit'] else 'BLANK'} | "
                f"Count={count} spot(s) | Bitmap={width_bytes}B x {height_dots}dots = {len(raw_bitmap)}B\n"
            )
            sys.stderr.flush()

        items_summary = []
        for idx, item in enumerate(items):
            qty = max(1, min(500, int(item.get("copies") or item.get("quantity") or 1)))
            img = item["image"]
            width_px, height_px = img.size
            width_mm = round((width_px / 203.0) * 25.4, 1)
            height_mm = round((height_px / 203.0) * 25.4, 1)
            items_summary.append({
                "item_index": idx + 1,
                "product_id": str(item.get("product_id") or item.get("id") or item.get("sku") or f"item_{idx + 1}"),
                "product_name": str(item.get("product_name") or ""),
                "sku": str(item.get("sku") or ""),
                "requested_quantity": qty,
                "image_width_px": width_px,
                "image_height_px": height_px,
                "image_width_mm": width_mm,
                "image_height_mm": height_mm,
                "printer_dpi": 203,
                "bitmap_width_bytes": 38,
                "bitmap_payload_length": 7600,
                "configured_label_height_mm": height_mm,
                "configured_gap_mm": gap_mm,
                "tspl_command": "TWIN-MODE: 38x25mm carrier with 2 sections",
            })

        return bytes(payload), items_summary

    @classmethod
    def submit_batch_job_raw_tspl(
        cls,
        printer_name: str,
        items: List[Dict[str, Any]],
        doc_name: str = "Kayan ERP TSPL Batch",
        default_width_mm: float = 38.0,
        default_height_mm: float = 13.0,
        gap_mm: float = 2.0,
        direction: int = 1,
        include_direction: bool = True,
        dry_run: bool = False
    ) -> Dict[str, Any]:
        """
        Send a sequence of product labels as a single continuous RAW TSPL job.
        Bypasses Windows GDI page boundaries and driver stock defaults.
        CRITICAL: Does NOT call StartPagePrinter/EndPagePrinter, preventing Windows
        winprint.dll from injecting unwanted Form Feed (0x0C) characters between products.

        Args:
            include_direction: When True (default), DIRECTION is included in the job header.
                Set False only for controlled diagnostic testing (H2 hypothesis).
        """
        first_img = items[0].get("image") if items else None
        if first_img:
            width_px_first, height_px_first = first_img.size
            nominal_width_mm = round((width_px_first / 203.0) * 25.4, 1) or default_width_mm
            nominal_height_mm = round((height_px_first / 203.0) * 25.4, 1) or default_height_mm
        else:
            nominal_width_mm = default_width_mm
            nominal_height_mm = default_height_mm

        if cls.is_twin_section_target(printer_name, nominal_width_mm, nominal_height_mm):
            payload, items_summary = cls.build_twin_batch_tspl_payload(
                items=items,
                gap_mm=gap_mm,
                direction=direction,
                include_direction=include_direction,
            )
        else:
            payload, items_summary = cls.build_batch_tspl_payload(
                items=items,
                default_width_mm=default_width_mm,
                default_height_mm=default_height_mm,
                gap_mm=gap_mm,
                direction=direction,
                include_direction=include_direction,
            )


        total_labels = sum(item["requested_quantity"] for item in items_summary)

        if dry_run or not HAS_WIN32:
            return {
                "success": True,
                "status": "DRY_RUN_RENDERED",
                "mode": "RAW_TSPL",
                "printer": printer_name,
                "total_products": len(items),
                "total_labels": total_labels,
                "copies": total_labels,
                "payload_bytes": len(payload),
                "items_summary": items_summary,
                "message": (
                    f"Dry-run TSPL batch simulation succeeded for '{printer_name}' "
                    f"({len(items)} products, {total_labels} total labels, {len(payload)} bytes)."
                )
            }

        cls.validate_printer(printer_name)

        hprinter = None
        try:
            hprinter = win32print.OpenPrinter(printer_name)
            # StartDocPrinter in RAW mode
            job_id = win32print.StartDocPrinter(hprinter, 1, (doc_name, None, "RAW"))
            try:
                # Direct WritePrinter without StartPagePrinter / EndPagePrinter
                # to prevent winprint from appending 0x0C Form Feed between labels
                bytes_written = win32print.WritePrinter(hprinter, payload)
                sys.stderr.write(
                    f"[DIAGNOSTIC-SPOOLER] JobID={job_id} | PayloadLen={len(payload)} | "
                    f"BytesWritten={bytes_written} | ExpectedLabels={total_labels} | Printer='{printer_name}'\n"
                )
                sys.stderr.flush()
                if bytes_written != len(payload):
                    raise PrinterSpoolerError(
                        f"Truncated write to '{printer_name}': wrote {bytes_written} of {len(payload)} bytes."
                    )
            finally:
                win32print.EndDocPrinter(hprinter)

            return {
                "success": True,
                "status": "SPOOLED",
                "mode": "RAW_TSPL",
                "printer": printer_name,
                "total_products": len(items),
                "total_labels": total_labels,
                "copies": total_labels,
                "payload_bytes": len(payload),
                "items_summary": items_summary,
                "message": (
                    f"Successfully sent batch of {len(items)} product(s) ({total_labels} total labels) "
                    f"via native TSPL RAW to '{printer_name}'."
                )
            }
        except Exception as e:
            raise PrinterSpoolerError(
                f"Failed to submit RAW TSPL batch print job to '{printer_name}': {str(e)}"
            ) from e
        finally:
            if hprinter is not None:
                try:
                    win32print.ClosePrinter(hprinter)
                except Exception:
                    pass

    @classmethod
    def build_escpos_payload(cls, items: List[Dict[str, Any]], cut: bool = True) -> bytes:
        """
        Convert a list of rendered receipt image items into a single continuous ESC/POS byte stream.
        Uses standard GS v 0 raster bit image commands (compatible with Xprinter XP-80C / POS-80 / Epson).
        """
        payload = bytearray()
        # Initialize printer: ESC @
        payload.extend(b"\x1b@")

        for item in items:
            img = item["image"]
            copies = max(1, min(500, int(item.get("copies") or 1)))

            # Convert to monochrome 1-bit
            if img.mode in ("RGBA", "LA") or (img.mode == "P" and "transparency" in getattr(img, "info", {})):
                bg = Image.new("RGB", img.size, (255, 255, 255))
                if img.mode == "P":
                    img = img.convert("RGBA")
                bg.paste(img, mask=img.split()[-1])
                img = bg

            gray = img.convert("L")
            w, h = gray.size

            # Standard 80mm printable width is 576 dots; ensure width is divisible by 8
            target_w = ((w + 7) // 8) * 8
            if target_w != w:
                padded = Image.new("L", (target_w, h), 255)
                padded.paste(gray, (0, 0))
                gray = padded
                w = target_w

            # Threshold to 1-bit: black = 1, white = 0
            bw = gray.point(lambda p: 1 if p < 180 else 0, mode="1")
            raw_bits = bw.tobytes()

            xL = (w // 8) % 256
            xH = (w // 8) // 256
            yL = h % 256
            yH = h // 256

            # GS v 0 m xL xH yL yH [data]
            raster_cmd = b"\x1dv0\x00" + bytes([xL, xH, yL, yH]) + raw_bits

            for _ in range(copies):
                payload.extend(raster_cmd)

        # Feed 4 lines: ESC d 4
        payload.extend(b"\x1bd\x04")
        if cut:
            # Partial paper cut: GS V 66 0
            payload.extend(b"\x1dV\x42\x00")

        return bytes(payload)

    @classmethod
    def submit_batch_job_raw_escpos(
        cls,
        printer_name: str,
        items: List[Dict[str, Any]],
        doc_name: str = "Kayan ERP ESC/POS Receipt",
        cut: bool = True,
        dry_run: bool = False
    ) -> Dict[str, Any]:
        """
        Send thermal receipt images as native ESC/POS RAW raster bytes directly to the printer.
        Supports both Windows Print Spooler RAW mode and direct UNC network share writing (\\\\host\\share).
        """
        payload = cls.build_escpos_payload(items, cut=cut)
        total_receipts = sum(max(1, int(item.get("copies") or 1)) for item in items)

        if dry_run or not HAS_WIN32:
            return {
                "success": True,
                "status": "DRY_RUN_RENDERED",
                "mode": "RAW_ESCPOS",
                "printer": printer_name,
                "total_receipts": total_receipts,
                "payload_bytes": len(payload),
                "message": f"Dry-run ESC/POS simulation succeeded for '{printer_name}' ({len(payload)} bytes rendered)."
            }

        cls.validate_printer(printer_name)

        is_unc = printer_name.startswith(r"\\") or printer_name.startswith("//")
        last_error = None

        # Method 1: Try Windows Print Spooler RAW mode
        try:
            hprinter = win32print.OpenPrinter(printer_name)
            try:
                win32print.StartDocPrinter(hprinter, 1, (doc_name, None, "RAW"))
                try:
                    bytes_written = win32print.WritePrinter(hprinter, payload)
                    if bytes_written != len(payload):
                        raise PrinterSpoolerError(
                            f"Truncated ESC/POS write: wrote {bytes_written} of {len(payload)} bytes."
                        )
                finally:
                    win32print.EndDocPrinter(hprinter)
            finally:
                win32print.ClosePrinter(hprinter)

            return {
                "success": True,
                "status": "SPOOLED",
                "mode": "RAW_ESCPOS",
                "printer": printer_name,
                "total_receipts": total_receipts,
                "payload_bytes": len(payload),
                "message": f"Successfully spooled ESC/POS receipt to '{printer_name}' via Windows Spooler."
            }
        except Exception as e:
            last_error = e
            sys.stderr.write(f"[printer_service] Windows Spooler RAW for '{printer_name}' failed: {e}\n")

        # Method 2: If UNC network path, try direct writing to the shared printer port/queue
        if is_unc:
            norm_unc = printer_name.replace("/", "\\")
            # Try win32file.CreateFile
            try:
                handle = win32file.CreateFile(
                    norm_unc,
                    win32con.GENERIC_WRITE,
                    win32con.FILE_SHARE_READ | win32con.FILE_SHARE_WRITE,
                    None,
                    win32con.OPEN_EXISTING,
                    0,
                    None
                )
                try:
                    err, bytes_written = win32file.WriteFile(handle, payload)
                    if err == 0:
                        return {
                            "success": True,
                            "status": "DIRECT_UNC_WRITTEN",
                            "mode": "RAW_ESCPOS_DIRECT",
                            "printer": printer_name,
                            "total_receipts": total_receipts,
                            "payload_bytes": len(payload),
                            "message": f"Successfully wrote ESC/POS receipt directly to network share '{norm_unc}'."
                        }
                finally:
                    win32file.CloseHandle(handle)
            except Exception as e_file:
                sys.stderr.write(f"[printer_service] Direct win32file write to '{norm_unc}' failed: {e_file}\n")

            # Try cmd /c copy /b temporary file to UNC path (standard Windows network printing)
            import tempfile, subprocess
            tmp_path = None
            try:
                with tempfile.NamedTemporaryFile(delete=False, suffix=".bin") as tmp:
                    tmp.write(payload)
                    tmp_path = tmp.name

                cmd = f'copy /b "{tmp_path}" {norm_unc}'
                res = subprocess.run(["cmd", "/c", cmd], capture_output=True, text=True, timeout=10)
                if res.returncode == 0:
                    return {
                        "success": True,
                        "status": "DIRECT_UNC_COPIED",
                        "mode": "RAW_ESCPOS_COPY",
                        "printer": printer_name,
                        "total_receipts": total_receipts,
                        "payload_bytes": len(payload),
                        "message": f"Successfully copied ESC/POS payload to network share '{norm_unc}'."
                    }
                else:
                    err_msg = (res.stderr or res.stdout or "").strip()
                    sys.stderr.write(f"[printer_service] copy /b failed: {err_msg}\n")
            except Exception as e_copy:
                sys.stderr.write(f"[printer_service] Direct copy /b to '{norm_unc}' failed: {e_copy}\n")
            finally:
                if tmp_path and os.path.exists(tmp_path):
                    try:
                        os.unlink(tmp_path)
                    except Exception:
                        pass

        if is_unc and "1326" in str(last_error):
            raise PrinterSpoolerError(
                f"تعذر الاتصال بطابعة الشبكة '{printer_name}': ويندوز يرفض الاتصال لطلب كلمة مرور (Error 1326). "
                f"يرجى إيقاف 'Password Protected Sharing' في إعدادات مشاركة ويندوز على جهاز الكاشير (192.168.0.41)، "
                f"أو ضبط THERMAL_PRINTER_USER و THERMAL_PRINTER_PASSWORD في .env، أو استخدام طباعة المتصفح المباشرة."
            )

        raise PrinterSpoolerError(
            f"Failed to submit ESC/POS receipt print job to '{printer_name}': {last_error}"
        )

    @classmethod
    def submit_batch_job_gdi(
        cls,
        printer_name: str,
        items: List[Dict[str, Any]],
        doc_name: str = "Kayan ERP GDI Batch",
        dry_run: bool = False
    ) -> Dict[str, Any]:
        """
        Submit a sequence of product labels via Windows GDI Device Context.
        Maintains all labels within a single GDI print document.
        """
        total_labels = sum(max(1, int(item.get("copies") or 1)) for item in items)

        if dry_run or not HAS_WIN32:
            return {
                "success": True,
                "status": "DRY_RUN_RENDERED",
                "mode": "GDI",
                "printer": printer_name,
                "total_products": len(items),
                "total_labels": total_labels,
                "copies": total_labels,
                "message": f"Dry-run simulation succeeded for printer '{printer_name}' ({total_labels} pages rendered, not sent to spooler)."
            }

        cls.validate_printer(printer_name)

        dc = None
        try:
            dc = win32ui.CreateDC()
            dc.CreatePrinterDC(printer_name)

            dpi_x = dc.GetDeviceCaps(win32con.LOGPIXELSX) or 203
            dpi_y = dc.GetDeviceCaps(win32con.LOGPIXELSY) or 203
            horz_res = dc.GetDeviceCaps(win32con.HORZRES) or 0
            vert_res = dc.GetDeviceCaps(win32con.VERTRES) or 0

            dc.StartDoc(doc_name)

            for item in items:
                img = item["image"]
                copies = max(1, int(item.get("copies") or 1))
                img_dpi = int(item.get("dpi") or 203)
                w, h = img.size
                dib = ImageWin.Dib(img)

                # Compute scaled target dimensions on device DC
                scale_x = float(dpi_x) / float(img_dpi)
                scale_y = float(dpi_y) / float(img_dpi)
                dst_w = int(round(w * scale_x))
                dst_h = int(round(h * scale_y))

                # If document is an office page (e.g. A4/A5 on 600 DPI printer),
                # clamp proportionally to printable area if needed
                if horz_res > 1000 and dst_w > horz_res:
                    ratio = float(horz_res) / float(dst_w)
                    dst_w = horz_res
                    dst_h = int(round(dst_h * ratio))
                if vert_res > 1000 and dst_h > vert_res:
                    ratio = float(vert_res) / float(dst_h)
                    dst_h = vert_res
                    dst_w = int(round(dst_w * ratio))

                for _ in range(copies):
                    dc.StartPage()
                    dib.draw(dc.GetHandleOutput(), (0, 0, dst_w, dst_h))
                    dc.EndPage()

            dc.EndDoc()

            return {
                "success": True,
                "status": "SPOOLED",
                "mode": "GDI",
                "printer": printer_name,
                "total_products": len(items),
                "total_labels": total_labels,
                "copies": total_labels,
                "message": f"Successfully spooled {total_labels} page(s) via GDI to '{printer_name}'."
            }
        except Exception as e:
            # If GDI fails and target is an ESC/POS printer or UNC path, try fallback to RAW ESC/POS
            if cls.is_escpos_compatible(printer_name) or printer_name.startswith(r"\\") or printer_name.startswith("//"):
                sys.stderr.write(f"[printer_service] GDI failed for '{printer_name}', trying RAW ESC/POS fallback: {e}\n")
                try:
                    return cls.submit_batch_job_raw_escpos(
                        printer_name=printer_name,
                        items=items,
                        doc_name=doc_name,
                        dry_run=dry_run
                    )
                except Exception as fallback_e:
                    raise PrinterSpoolerError(
                        f"Failed GDI and RAW ESC/POS print jobs to '{printer_name}': {e} | Fallback error: {fallback_e}"
                    ) from fallback_e

            raise PrinterSpoolerError(
                f"Failed to submit GDI batch print job to printer '{printer_name}': {str(e)}"
            ) from e
        finally:
            if dc is not None:
                try:
                    dc.DeleteDC()
                except Exception:
                    pass

    @classmethod
    def submit_batch_job(
        cls,
        printer_name: str,
        items: List[Dict[str, Any]],
        doc_name: str = "Kayan ERP Print Job",
        gap_mm: float = 2.0,
        direction: int = 0,
        include_direction: bool = True,
        print_mode: str = "auto",
        dry_run: bool = False
    ) -> Dict[str, Any]:
        """
        Submit a batch sequence of product labels to the Windows print spooler.
        Supports print_mode: 'auto' (default), 'raw_tspl', 'raw_escpos', or 'gdi'.
        """
        if not items:
            raise ValueError("No items provided for printing.")

        mode_req = str(print_mode or "auto").lower().strip()

        if mode_req == "raw_tspl" or (mode_req == "auto" and cls.is_tspl_compatible(printer_name)):
            try:
                return cls.submit_batch_job_raw_tspl(
                    printer_name=printer_name,
                    items=items,
                    doc_name=doc_name,
                    gap_mm=gap_mm,
                    direction=direction,
                    include_direction=include_direction,
                    dry_run=dry_run,
                )
            except Exception as e:
                if mode_req == "raw_tspl":
                    raise
                sys.stderr.write(
                    f"Warning: TSPL RAW batch mode failed for '{printer_name}', falling back to GDI: {e}\n"
                )

        if mode_req == "raw_escpos" or (mode_req == "auto" and cls.is_escpos_compatible(printer_name)):
            try:
                return cls.submit_batch_job_raw_escpos(
                    printer_name=printer_name,
                    items=items,
                    doc_name=doc_name,
                    dry_run=dry_run
                )
            except Exception as e:
                if mode_req == "raw_escpos":
                    raise
                sys.stderr.write(
                    f"Warning: ESC/POS RAW mode failed for '{printer_name}', falling back to GDI: {e}\n"
                )

        return cls.submit_batch_job_gdi(
            printer_name=printer_name,
            items=items,
            doc_name=doc_name,
            dry_run=dry_run
        )


    @classmethod
    def submit_job(
        cls,
        printer_name: str,
        images: List[Image.Image],
        doc_name: str = "Kayan ERP Print Job",
        copies: int = 1,
        gap_mm: float = 2.0,
        direction: int = 1,
        dry_run: bool = False
    ) -> Dict[str, Any]:
        """
        Backward-compatible submission for single-item or uniform image lists.
        Wraps images into standardized batch items and routes to submit_batch_job.
        """
        if not images:
            raise ValueError("No images provided for printing.")

        copies = max(1, min(500, int(copies)))

        # Wrap images as batch items
        # If multiple images are passed with a single copy count, each image gets `copies`
        batch_items = [
            {
                "image": img,
                "copies": copies,
                "product_id": f"page_{idx + 1}",
                "product_name": f"Label Page {idx + 1}",
            }
            for idx, img in enumerate(images)
        ]

        result = cls.submit_batch_job(
            printer_name=printer_name,
            items=batch_items,
            doc_name=doc_name,
            gap_mm=gap_mm,
            direction=direction,
            dry_run=dry_run
        )

        # Retain backward-compatible top-level keys
        result["pages"] = len(images)
        result["copies"] = copies
        result["spooled_total"] = len(images) * copies
        return result
