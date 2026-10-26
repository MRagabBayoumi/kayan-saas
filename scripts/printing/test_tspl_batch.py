"""
Controlled Batch Printing Test Suite for Kayan ERP TSPL Printing Engine.

Covers the 5 required test cases:
  1. One product, one label (expected: 1 label)
  2. One product, two copies (expected: 2 labels)
  3. Two different products, one label each (expected: 2 labels)
  4. Three different products with quantities 2, 1, and 3 (expected: 6 labels)
  5. Repeated batches submitted consecutively (Batch 1: 2 labels, Batch 2: 3 labels)

SAFETY: Defaults to dry_run=True. Physical hardware output is ONLY sent when
--physical-print is explicitly passed on the command line.
"""

import argparse
import json
import os
import sys
from typing import Any, Dict, List

# Ensure scripts/printing is on python path
_current_dir = os.path.dirname(os.path.abspath(__file__))
if _current_dir not in sys.path:
    sys.path.insert(0, _current_dir)

from template_registry import registry
from printer_service import PrinterService


def run_test_scenario(
    name: str,
    items_def: List[Dict[str, Any]],
    printer_name: str,
    dry_run: bool = True
) -> Dict[str, Any]:
    """Execute a single controlled test scenario and validate all TSPL constraints."""
    template = registry.get("barcode_label")
    batch_items = []
    expected_total_labels = sum(item["copies"] for item in items_def)

    sys.stdout.write(f"\n{'='*70}\n")
    sys.stdout.write(f"SCENARIO: {name}\n")
    sys.stdout.write(f"Expected Labels: {expected_total_labels} | Mode: {'PHYSICAL' if not dry_run else 'DRY_RUN'}\n")
    sys.stdout.write(f"{'='*70}\n")

    # Render each item
    for idx, item in enumerate(items_def):
        data = {
            "product_name": item["name"],
            "barcode": item["barcode"],
            "sku": item["sku"],
            "width_mm": item.get("width_mm", 38),
            "height_mm": item.get("height_mm", 13),
            "show_company_name": False,
            "show_product_name": True,
            "show_barcode_bars": True,
            "show_item_sku": True,
            "print_price_type": "none",
            "product_font_size": 16,
            "details_font_size": 14,
            "barcode_width_px": 260,
            "barcode_height_px": 50,
        }
        images = template.render(data)
        img = images[0]

        batch_items.append({
            "image": img,
            "copies": item["copies"],
            "product_id": item["sku"],
            "product_name": item["name"],
            "sku": item["sku"],
            "dpi": 203,
            "width_mm": data["width_mm"],
            "height_mm": data["height_mm"],
        })

    # Submit via PrinterService
    result = PrinterService.submit_batch_job(
        printer_name=printer_name,
        items=batch_items,
        doc_name=f"Test - {name}",
        gap_mm=2.0,
        direction=1,
        dry_run=dry_run
    )

    # Verification checks
    actual_labels = result.get("total_labels", 0)
    passed = (actual_labels == expected_total_labels) and result.get("success", False)

    report = {
        "scenario": name,
        "passed": passed,
        "expected_labels": expected_total_labels,
        "actual_labels": actual_labels,
        "total_products": len(items_def),
        "status": result.get("status"),
        "mode": result.get("mode"),
        "payload_bytes": result.get("payload_bytes"),
        "items_summary": result.get("items_summary", []),
    }

    sys.stdout.write(f"Result: {'PASS [OK]' if passed else 'FAIL [X]'} (Expected: {expected_total_labels}, Actual: {actual_labels})\n")
    return report


def main():
    parser = argparse.ArgumentParser(description="Kayan ERP TSPL Batch Test Suite")
    parser.add_argument("--printer", type=str, default="Xprinter XP-233B", help="Target printer name")
    parser.add_argument("--physical-print", action="store_true", help="Submit jobs physically to Windows Spooler")
    parser.add_argument("--scenario", type=int, choices=[1, 2, 3, 4, 5], help="Run a single specific scenario (1-5)")
    args = parser.parse_args()

    is_dry_run = not args.physical_print
    printer_name = args.printer

    sys.stdout.write(f"Starting Kayan ERP Controlled Batch Tests\n")
    sys.stdout.write(f"Target Printer: '{printer_name}' | Execution: {'DRY_RUN (Safe, no physical printing)' if is_dry_run else 'PHYSICAL HARDWARE PRINT'}\n")

    scenarios = [
        # Scenario 1: One product, one label
        {
            "num": 1,
            "name": "1. One product, one label (1 copy)",
            "items": [
                {"name": "صابون غسيل فاخر", "barcode": "622100000001", "sku": "SOAP-01", "copies": 1},
            ],
        },
        # Scenario 2: One product, two copies
        {
            "num": 2,
            "name": "2. One product, two copies (2 copies of same product)",
            "items": [
                {"name": "شامبو شعر طبيعي", "barcode": "622100000002", "sku": "SHAMP-02", "copies": 2},
            ],
        },
        # Scenario 3: Two different products, one label each
        {
            "num": 3,
            "name": "3. Two different products, one label each (1+1=2)",
            "items": [
                {"name": "معجون أسنان مبيض", "barcode": "622100000003", "sku": "PASTE-03", "copies": 1},
                {"name": "فرشاة أسنان ناعمة", "barcode": "622100000004", "sku": "BRUSH-04", "copies": 1},
            ],
        },
        # Scenario 4: Three different products with quantities 2, 1, and 3
        {
            "num": 4,
            "name": "4. Three different products with quantities 2, 1, and 3 (2+1+3=6)",
            "items": [
                {"name": "ليفه استحمام", "barcode": "622100000005", "sku": "UB-01", "copies": 2},
                {"name": "شفرات حلاقة موس", "barcode": "622100000006", "sku": "BLADE-02", "copies": 1},
                {"name": "ليفة جسم كورية", "barcode": "622100000007", "sku": "KOR-03", "copies": 3},
            ],
        },
        # Scenario 5: Repeated batches submitted consecutively
        {
            "num": 5,
            "name": "5. Repeated batches submitted consecutively (Batch A: 2 labels, Batch B: 3 labels)",
            "items_batch_a": [
                {"name": "كريم مرطب لليدين", "barcode": "622100000008", "sku": "CREAM-08", "copies": 2},
            ],
            "items_batch_b": [
                {"name": "غسول وجه منعش", "barcode": "622100000009", "sku": "WASH-09", "copies": 1},
                {"name": "سيروم فيتامين سي", "barcode": "622100000010", "sku": "SERUM-10", "copies": 2},
            ],
        },
    ]

    all_reports = []

    for sc in scenarios:
        if args.scenario and sc["num"] != args.scenario:
            continue

        if sc["num"] == 5:
            # Run Batch A followed by Batch B
            rep_a = run_test_scenario("5A. Consecutive Batch 1", sc["items_batch_a"], printer_name, dry_run=is_dry_run)
            rep_b = run_test_scenario("5B. Consecutive Batch 2", sc["items_batch_b"], printer_name, dry_run=is_dry_run)
            passed = rep_a["passed"] and rep_b["passed"]
            all_reports.append({
                "scenario": sc["name"],
                "passed": passed,
                "batch_a": rep_a,
                "batch_b": rep_b,
                "expected_labels": rep_a["expected_labels"] + rep_b["expected_labels"],
                "actual_labels": rep_a["actual_labels"] + rep_b["actual_labels"],
            })
        else:
            rep = run_test_scenario(sc["name"], sc["items"], printer_name, dry_run=is_dry_run)
            all_reports.append(rep)

    sys.stdout.write(f"\n{'='*70}\n")
    sys.stdout.write(f"SUMMARY OF ALL {len(all_reports)} SCENARIOS:\n")
    sys.stdout.write(f"{'='*70}\n")
    all_passed = True
    for r in all_reports:
        mark = "PASS" if r["passed"] else "FAIL"
        if not r["passed"]:
            all_passed = False
        sys.stdout.write(f"[{mark}] {r['scenario']}: Expected {r['expected_labels']}, Got {r['actual_labels']}\n")

    sys.stdout.write(f"{'='*70}\n")
    sys.stdout.write(f"FINAL RESULT: {'ALL TESTS PASSED' if all_passed else 'SOME TESTS FAILED'}\n")
    sys.stdout.write(f"{'='*70}\n")

    sys.exit(0 if all_passed else 1)


if __name__ == "__main__":
    main()
