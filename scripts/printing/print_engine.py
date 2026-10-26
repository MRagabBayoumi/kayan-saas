"""
Kayan ERP — Centralized Python Printing Engine
Main entry point and job dispatcher.

Accepts validated print jobs, resolves templates via TemplateRegistry,
renders document bitmaps, and submits them to Windows Spooler via PrinterService.

Usage Examples:
    python print_engine.py --list-templates
    python print_engine.py --list-printers
    python print_engine.py --dry-run --job '{"template_id":"barcode_label","printer":"Xprinter XP-233B","data":{"product_name":"اكلادور","barcode":"12345678"}}'
    python print_engine.py --stdin < job.json
"""

import argparse
import json
import os
import sys
from typing import Any, Dict

# Force UTF-8 on Windows stdin/stdout/stderr
if hasattr(sys.stdin, 'reconfigure'):
    try:
        sys.stdin.reconfigure(encoding='utf-8')
    except Exception:
        pass
if hasattr(sys.stdout, 'reconfigure'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass
if hasattr(sys.stderr, 'reconfigure'):
    try:
        sys.stderr.reconfigure(encoding='utf-8')
    except Exception:
        pass

# Ensure scripts/printing is on python path
_current_dir = os.path.dirname(os.path.abspath(__file__))
_parent_dir = os.path.dirname(_current_dir)
if _current_dir not in sys.path:
    sys.path.insert(0, _current_dir)
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from template_registry import registry, UnknownTemplateError
from templates.base_template import TemplateValidationError
from printer_service import PrinterService, PrinterNotFoundError, PrinterSpoolerError


def output_json(response: Dict[str, Any], exit_code: int = 0) -> None:
    """Print structured JSON to stdout (guaranteed UTF-8) and exit."""
    json_str = json.dumps(response, ensure_ascii=False, indent=2)
    if hasattr(sys.stdout, 'buffer'):
        sys.stdout.buffer.write(json_str.encode('utf-8'))
        sys.stdout.buffer.write(b"\n")
        sys.stdout.buffer.flush()
    else:
        print(json_str)
    sys.exit(exit_code)


def log_diagnostic(msg: str) -> None:
    """Write diagnostic message to stderr (never interferes with stdout JSON)."""
    if hasattr(sys.stderr, 'buffer'):
        sys.stderr.buffer.write(f"[print_engine] {msg}\n".encode('utf-8', errors='replace'))
        sys.stderr.buffer.flush()
    else:
        sys.stderr.write(f"[print_engine] {msg}\n")
        sys.stderr.flush()



def run_print_job(job_payload: Dict[str, Any], dry_run: bool = False, output_image_path: str = None) -> Dict[str, Any]:
    """
    Execute a print job payload:
    Supports both single jobs and multi-product batch jobs.
    1. Parse and validate job structure
    2. Lookup template(s) in TemplateRegistry
    3. Validate and render document images
    4. Save rendered image(s) if requested
    5. Submit as an atomic single or batch print job to Windows Spooler
    """
    if not isinstance(job_payload, dict):
        raise ValueError(f"Job payload must be a JSON object, got {type(job_payload).__name__}")

    # Pass optional network authentication credentials to environment
    if job_payload.get("network_user"):
        os.environ["THERMAL_PRINTER_USER"] = str(job_payload["network_user"])
    if job_payload.get("network_password"):
        os.environ["THERMAL_PRINTER_PASSWORD"] = str(job_payload["network_password"])

    raw_printer = str(job_payload.get("printer") or job_payload.get("printer_name") or "").strip()
    if not raw_printer:
        template_id = str(job_payload.get("template_id") or "").lower()
        if "receipt" in template_id or "thermal" in template_id:
            raw_printer = os.environ.get("THERMAL_PRINTER_PATH") or os.environ.get("PRINTING_DEFAULT_RECEIPT_PRINTER") or "XP-80C"
        else:
            raw_printer = os.environ.get("PRINTING_DEFAULT_PRINTER") or "Xprinter XP-233B"

    printer_name = PrinterService.resolve_printer_name(raw_printer)

    # Mode A: Multi-Product Batch Payload
    batch_raw = job_payload.get("batch") or job_payload.get("items")
    if batch_raw and isinstance(batch_raw, list):
        log_diagnostic(f"Dispatching multi-product batch ({len(batch_raw)} entries) for printer '{printer_name}'")
        batch_items = []
        total_requested_labels = 0
        saved_images = []

        for idx, entry in enumerate(batch_raw):
            if not isinstance(entry, dict):
                continue
            entry_tpl_id = entry.get("template_id") or "barcode_label"
            entry_tpl = registry.get(entry_tpl_id)
            entry_data = entry.get("data") or {}
            entry_copies = max(1, min(500, int(entry.get("copies") or entry.get("quantity") or entry.get("qty") or 1)))

            log_diagnostic(f"Rendering batch item [{idx + 1}/{len(batch_raw)}] using {entry_tpl_id} (copies: {entry_copies})")
            rendered_pages = entry_tpl.render(entry_data)
            if not rendered_pages:
                raise RuntimeError(f"Template '{entry_tpl_id}' returned 0 pages for batch item {idx + 1}")

            for p_idx, p_img in enumerate(rendered_pages):
                batch_items.append({
                    "image": p_img,
                    "copies": entry_copies,
                    "product_id": entry_data.get("product_id") or entry_data.get("id") or entry_data.get("sku") or f"prod_{idx + 1}",
                    "product_name": entry_data.get("product_name", ""),
                    "sku": entry_data.get("sku", ""),
                    "dpi": int(entry_data.get("dpi") or 203),
                    "width_mm": float(entry_data.get("width_mm") or 38),
                    "height_mm": float(entry_data.get("height_mm") or 13),
                })
                total_requested_labels += entry_copies

                if output_image_path:
                    base_name, ext = os.path.splitext(output_image_path)
                    ext = ext or ".png"
                    target = f"{base_name}_item_{idx + 1}_p{p_idx + 1}{ext}"
                    os.makedirs(os.path.dirname(os.path.abspath(target)), exist_ok=True)
                    p_img.save(target)
                    saved_images.append(target)

        if not batch_items:
            raise ValueError("Batch payload contained 0 valid items to print.")

        first_data = (batch_raw[0].get("data") if isinstance(batch_raw[0], dict) else {}) or {}
        gap_mm = float(first_data.get("gap_mm") or job_payload.get("gap_mm") or 2.0)
        direction = int(first_data.get("direction") or job_payload.get("direction") or 0)
        print_mode = str(first_data.get("print_mode") or job_payload.get("print_mode") or "auto")

        spool_result = PrinterService.submit_batch_job(
            printer_name=printer_name,
            items=batch_items,
            doc_name="Kayan ERP Batch Print",
            gap_mm=gap_mm,
            direction=direction,
            print_mode=print_mode,
            dry_run=dry_run
        )

        response = {
            "success": True,
            "batch_mode": True,
            "printer": printer_name,
            "total_products": len(batch_items),
            "total_labels": total_requested_labels,
            "copies": total_requested_labels,
            "items_summary": spool_result.get("items_summary", []),
            "status": spool_result["status"],
            "message": spool_result["message"],
        }
        if saved_images:
            response["saved_images"] = saved_images
        return response

    # Mode B: Single Job Payload (backward-compatible)
    template_id = job_payload.get("template_id")
    if not template_id:
        raise ValueError("Missing 'template_id' in job payload.")

    copies = max(1, min(500, int(job_payload.get("copies") or job_payload.get("quantity") or 1)))
    data = job_payload.get("data") or {}

    log_diagnostic(f"Dispatching template '{template_id}' for printer '{printer_name}' (copies: {copies})")

    # 1. Resolve template
    template = registry.get(template_id)

    # 2. Render document images
    log_diagnostic(f"Rendering document with {template.__class__.__name__}...")
    images = template.render(data)
    if not images:
        raise RuntimeError(f"Template '{template_id}' returned 0 rendered pages.")

    log_diagnostic(f"Rendered {len(images)} page(s) successfully.")

    # 3. Optional: save image to disk for inspection or preview
    saved_images = []
    if output_image_path:
        base_name, ext = os.path.splitext(output_image_path)
        ext = ext or ".png"
        for idx, img in enumerate(images):
            target = output_image_path if len(images) == 1 else f"{base_name}_{idx + 1}{ext}"
            os.makedirs(os.path.dirname(os.path.abspath(target)), exist_ok=True)
            img.save(target)
            saved_images.append(target)
            log_diagnostic(f"Saved rendered preview: {target}")

    # 4. Spool to printer
    gap_mm = float(data.get("gap_mm") or job_payload.get("gap_mm") or 2.0)
    direction = int(data.get("direction") or job_payload.get("direction") or 0)
    print_mode = str(data.get("print_mode") or job_payload.get("print_mode") or "auto")

    batch_items = [
        {
            "image": img,
            "copies": copies,
            "product_id": data.get("product_id") or data.get("id") or data.get("sku") or f"page_{idx + 1}",
            "product_name": data.get("product_name", ""),
            "sku": data.get("sku", ""),
            "dpi": int(data.get("dpi") or 203),
            "width_mm": float(data.get("width_mm") or 38),
            "height_mm": float(data.get("height_mm") or 13),
        }
        for idx, img in enumerate(images)
    ]

    spool_result = PrinterService.submit_batch_job(
        printer_name=printer_name,
        items=batch_items,
        doc_name=f"Kayan ERP - {template_id}",
        gap_mm=gap_mm,
        direction=direction,
        print_mode=print_mode,
        dry_run=dry_run
    )

    response = {
        "success": True,
        "template_id": template_id,
        "document_type": template.document_type,
        "printer": printer_name,
        "copies": copies,
        "pages_per_copy": len(images),
        "total_pages": len(images) * copies,
        "status": spool_result["status"],
        "message": spool_result["message"],
    }
    if saved_images:
        response["saved_images"] = saved_images

    return response


def main():
    parser = argparse.ArgumentParser(description="Kayan ERP Centralized Python Printing Engine")
    parser.add_argument("--job", type=str, help="Print job payload as JSON string")
    parser.add_argument("--file", type=str, help="Path to JSON file containing print job payload")
    parser.add_argument("--stdin", action="store_true", help="Read print job payload JSON from stdin")
    parser.add_argument("--list-templates", action="store_true", help="List registered print templates and metadata")
    parser.add_argument("--list-printers", action="store_true", help="List available Windows printers")
    parser.add_argument("--dry-run", action="store_true", help="Render document without sending to Windows spooler")
    parser.add_argument("--output-image", type=str, help="Optional file path to save rendered image preview (PNG)")

    args = parser.parse_args()

    # Mode 1: List templates
    if args.list_templates:
        output_json({
            "success": True,
            "action": "list_templates",
            "templates": registry.list_templates()
        })

    # Mode 2: List printers
    if args.list_printers:
        printers = PrinterService.get_available_printers()
        output_json({
            "success": True,
            "action": "list_printers",
            "printers": printers,
            "count": len(printers)
        })

    # Mode 3: Execute Print Job
    payload_str = None
    if args.job:
        payload_str = args.job
    elif args.file:
        if not os.path.exists(args.file):
            output_json({
                "success": False,
                "error_code": "FILE_NOT_FOUND",
                "message": f"Job file not found: {args.file}"
            }, exit_code=1)
        with open(args.file, "r", encoding="utf-8") as f:
            payload_str = f.read()
    elif args.stdin:
        if hasattr(sys.stdin, 'buffer'):
            payload_str = sys.stdin.buffer.read().decode('utf-8-sig', errors='replace')
        else:
            payload_str = sys.stdin.read()

    if payload_str:
        payload_str = payload_str.lstrip('\ufeff').strip()

    if not payload_str:
        parser.print_help(sys.stderr)
        output_json({
            "success": False,
            "error_code": "MISSING_INPUT",
            "message": "No job payload provided. Use --job, --file, --stdin, or --list-templates."
        }, exit_code=1)

    try:
        job_data = json.loads(payload_str)
    except json.JSONDecodeError as jde:
        output_json({
            "success": False,
            "error_code": "JSON_PARSE_ERROR",
            "message": f"Failed to parse job JSON: {str(jde)}"
        }, exit_code=1)

    # Allow --dry-run override from either CLI flag or JSON payload
    is_dry_run = args.dry_run or bool(job_data.get("dry_run", False))
    output_img = args.output_image or job_data.get("output_image")

    try:
        result = run_print_job(job_data, dry_run=is_dry_run, output_image_path=output_img)
        output_json(result, exit_code=0)

    except UnknownTemplateError as ute:
        log_diagnostic(f"UnknownTemplateError: {ute}")
        output_json({
            "success": False,
            "error_code": "UNKNOWN_TEMPLATE",
            "message": str(ute)
        }, exit_code=1)

    except TemplateValidationError as tve:
        log_diagnostic(f"TemplateValidationError: {tve}")
        output_json({
            "success": False,
            "error_code": "VALIDATION_ERROR",
            "message": str(tve)
        }, exit_code=1)

    except PrinterNotFoundError as pnfe:
        log_diagnostic(f"PrinterNotFoundError: {pnfe}")
        output_json({
            "success": False,
            "error_code": "PRINTER_NOT_FOUND",
            "message": str(pnfe)
        }, exit_code=2)

    except PrinterSpoolerError as pse:
        log_diagnostic(f"PrinterSpoolerError: {pse}")
        output_json({
            "success": False,
            "error_code": "SPOOLER_ERROR",
            "message": str(pse)
        }, exit_code=2)

    except Exception as exc:
        log_diagnostic(f"Unexpected error: {exc}")
        output_json({
            "success": False,
            "error_code": "SYSTEM_ERROR",
            "message": f"Unexpected error during printing: {str(exc)}"
        }, exit_code=3)


if __name__ == "__main__":
    main()
