"""
Diagnostic Test — H2: Does DIRECTION cause a blank-label calibration feed?

PURPOSE
-------
Tests whether omitting the DIRECTION command from the TSPL job header
eliminates the blank label that appears between products during batch printing.

WHAT THIS CHANGES (and does NOT change)
----------------------------------------
- OMITTED : DIRECTION {n}                  ← only change in the test stream
- UNCHANGED: SIZE, GAP, REFERENCE          ← job header commands
- UNCHANGED: CLS, BITMAP, PRINT 1,{qty}   ← per-product commands
- UNCHANGED: all bitmap dimensions, quantities, pixel math
- UNCHANGED: StartDocPrinter/WritePrinter/EndDocPrinter flow
- UNCHANGED: Laravel application / PHP files

USAGE
-----
Dry-run only (no printer):
    python test_direction_hypothesis.py --dry-run

Physical printer test (two products, 1 copy each, WITHOUT DIRECTION):
    python test_direction_hypothesis.py --physical-print

Restore original behavior (with DIRECTION) for comparison:
    python test_direction_hypothesis.py --physical-print --include-direction

RESTORE
-------
To restore: the include_direction flag defaults to True everywhere.
No files need to be reverted; the production code path is unchanged.
"""

import argparse
import sys
import os

# Ensure scripts/printing on path
_dir = os.path.dirname(os.path.abspath(__file__))
if _dir not in sys.path:
    sys.path.insert(0, _dir)

from PIL import Image, ImageDraw, ImageFont
from printer_service import PrinterService


def _make_test_label(text: str, width_px: int = 304, height_px: int = 104) -> Image.Image:
    """Create a minimal white label image with centered text for physical identification."""
    img = Image.new("RGB", (width_px, height_px), "white")
    draw = ImageDraw.Draw(img)
    # Use default font; no Arabic shaping needed for diagnostic labels
    draw.text((10, 35), text, fill="black")
    draw.rectangle([1, 1, width_px - 2, height_px - 2], outline="black", width=2)
    return img


def run_test(physical_print: bool, include_direction: bool, printer_name: str) -> None:
    mode_tag = "WITH DIRECTION" if include_direction else "WITHOUT DIRECTION (H2 test)"
    print_mode = "PHYSICAL PRINT" if physical_print else "DRY-RUN"

    print(f"\n{'='*60}")
    print(f"H2 Diagnostic Test — {mode_tag}")
    print(f"Mode: {print_mode}")
    print(f"Printer: {printer_name}")
    print(f"{'='*60}\n")

    # Two minimal test images — Product A and Product B
    img_a = _make_test_label("DIAG-A  Product A  Qty=1")
    img_b = _make_test_label("DIAG-B  Product B  Qty=1")

    items = [
        {
            "image": img_a,
            "copies": 1,
            "product_id": "DIAG-A",
            "product_name": "Diagnostic Product A",
            "sku": "DIAG-SKU-001",
        },
        {
            "image": img_b,
            "copies": 1,
            "product_id": "DIAG-B",
            "product_name": "Diagnostic Product B",
            "sku": "DIAG-SKU-002",
        },
    ]

    # Build payload for inspection
    print("--- TSPL Header Preview (no binary data) ---")
    payload, summary = PrinterService.build_batch_tspl_payload(
        items=items,
        gap_mm=2.0,
        direction=1,
        include_direction=include_direction,
    )

    # Print readable part of payload (text commands before first binary blob)
    printable_header = payload[:200].decode("ascii", errors="replace")
    printable_header_clean = printable_header.split("CLS")[0]
    print(repr(printable_header_clean))
    print()

    # Confirm DIRECTION presence
    direction_present = b"DIRECTION" in payload
    print(f"DIRECTION present in stream: {direction_present}")
    print(f"Expected DIRECTION present: {include_direction}")
    assert direction_present == include_direction, (
        f"DIRECTION mismatch! payload has DIRECTION={direction_present}, "
        f"expected={include_direction}"
    )
    print("[OK] Stream verification passed\n")

    # Print item summary
    for s in summary:
        print(
            f"  [{s['item_index']}/{len(summary)}] "
            f"ID={s['product_id']} | Qty={s['requested_quantity']} | "
            f"Bitmap={s['bitmap_width_bytes']}B × {s['image_height_px']}dots = "
            f"{s['bitmap_payload_length']}B | "
            f"Cmd: {s['tspl_command']}"
        )
    print()

    print(f"Total payload: {len(payload)} bytes")
    print(f"Total expected labels: {sum(s['requested_quantity'] for s in summary)}")
    print()

    if physical_print:
        print(">>> SENDING TO PHYSICAL PRINTER <<<")
        print("Observe the printer output carefully:")
        print("  - Expected: 2 labels printed (Label A, then Label B)")
        print("  - Watch for: blank label between A and B")
        print()

        result = PrinterService.submit_batch_job(
            printer_name=printer_name,
            items=items,
            doc_name=f"H2 Diag Test — {mode_tag}",
            gap_mm=2.0,
            direction=1,
            include_direction=include_direction,
            dry_run=False,
        )

        print(f"Spool result: {result['status']}")
        print(f"Mode used: {result.get('mode', 'unknown')}")
        print(f"Total labels reported: {result.get('total_labels')}")
        print()
        print("--- RECORD PHYSICAL RESULT ---")
        print("Labels printed (count)       : ___")
        print("Blank label between A and B? : YES / NO")
        print("Label A content correct?     : YES / NO")
        print("Label B content correct?     : YES / NO")
        print("Unexpected extra feed?        : YES / NO")
    else:
        print(">>> DRY-RUN ONLY — printer NOT contacted <<<")
        print("Run with --physical-print to send to hardware.")

    print(f"\n{'='*60}")
    print("Test complete. Record results above before next test.")
    print(f"{'='*60}\n")


def main() -> None:
    parser = argparse.ArgumentParser(description="H2 Diagnostic: DIRECTION command blank-label test")
    parser.add_argument("--physical-print", action="store_true",
                        help="Send job to physical printer (default: dry-run only)")
    parser.add_argument("--include-direction", action="store_true", default=False,
                        help="Include DIRECTION in stream (default: omit for H2 test)")
    parser.add_argument("--printer", type=str, default="Xprinter XP-233B",
                        help="Target printer name")
    args = parser.parse_args()

    run_test(
        physical_print=args.physical_print,
        include_direction=args.include_direction,
        printer_name=args.printer,
    )


if __name__ == "__main__":
    main()
