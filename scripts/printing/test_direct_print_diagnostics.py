"""Hardware-isolated diagnostics for direct TSPL barcode label printing."""

import contextlib
import io
import re
import sys
import unittest
from pathlib import Path
from typing import Any, Dict
from unittest.mock import patch

_current_dir = str(Path(__file__).resolve().parent)
if _current_dir not in sys.path:
    sys.path.insert(0, _current_dir)

import print_engine
import printer_service
from printer_service import PrinterService


class MockSpooler:
    """Captures RAW spooler calls without connecting to a Windows printer."""

    def __init__(self, expected_labels: int):
        self.expected_labels = expected_labels
        self.job_id = 7319
        self.payload = b""
        self.bytes_written = 0
        self.start_doc_calls = 0
        self.write_calls = 0
        self.end_doc_calls = 0
        self.open_calls = 0
        self.close_calls = 0

    def OpenPrinter(self, printer_name: str) -> str:
        self.open_calls += 1
        return "mock-printer-handle"

    def StartDocPrinter(self, handle: str, level: int, document: tuple) -> int:
        self.start_doc_calls += 1
        return self.job_id

    def WritePrinter(self, handle: str, payload: bytes) -> int:
        self.write_calls += 1
        self.payload = payload
        self.bytes_written = len(payload)
        sys.stderr.write(
            "[DIAGNOSTIC-MOCK] "
            f"job_id={self.job_id} "
            f"job_bytes={len(payload)} "
            f"bytes_written={self.bytes_written} "
            f"expected_labels={self.expected_labels}\n"
        )
        return self.bytes_written

    def EndDocPrinter(self, handle: str) -> None:
        self.end_doc_calls += 1

    def ClosePrinter(self, handle: str) -> None:
        self.close_calls += 1


def make_print_job(quantity: int) -> Dict[str, Any]:
    return {
        "template_id": "barcode_label",
        "printer_name": "Xprinter XP-233B",
        "copies": quantity,
        "data": {
            "product_id": "DIAGNOSTIC-001",
            "product_name": "Diagnostic product",
            "barcode": "6221234567890",
            "sku": "DIAGNOSTIC-001",
            "width_mm": 38,
            "height_mm": 15,
            "show_company_name": False,
            "show_product_name": True,
            "show_barcode_bars": True,
            "show_item_sku": True,
        },
    }


def inspect_tspl_payload(payload: bytes, expected_quantity: int) -> Dict[str, Any]:
    """Parse the known one-image TSPL layout and reject bytes outside its commands."""
    marker = b"CLS\r\nBITMAP "
    header, separator, first_block = payload.partition(marker)
    if not separator:
        raise AssertionError("TSPL payload has no CLS/BITMAP block")

    blocks = [first_block]
    blocks.extend(payload.split(marker)[2:])
    parsed_bitmaps = []
    print_commands = []
    command_bytes = bytearray(header)

    for block in blocks:
        match = re.match(rb"0,0,(\d+),(\d+),0,", block)
        if not match:
            raise AssertionError("Unexpected or malformed BITMAP command")

        width_bytes, height_px = (int(value) for value in match.groups())
        bitmap_start = match.end()
        bitmap_length = width_bytes * height_px
        bitmap_end = bitmap_start + bitmap_length
        expected_footer = re.match(rb"\r\nPRINT 1,(\d+)\r\n", block[bitmap_end:])
        if not expected_footer:
            raise AssertionError("Bitmap length or PRINT command boundary is invalid")

        command_quantity = int(expected_footer.group(1))
        trailing_bytes = block[bitmap_end + expected_footer.end():]
        if trailing_bytes:
            raise AssertionError(f"Unexpected bytes after PRINT command: {trailing_bytes!r}")

        parsed_bitmaps.append({
            "width_bytes": width_bytes,
            "height_px": height_px,
            "bitmap_length": bitmap_length,
        })
        print_commands.append(command_quantity)
        command_bytes.extend(marker)
        command_bytes.extend(block[:bitmap_start])
        command_bytes.extend(block[bitmap_end:bitmap_end + expected_footer.end()])

    expected_header = (
        b"SIZE 38 mm, 15 mm\r\n"
        b"GAP 2 mm, 0 mm\r\n"
        b"DIRECTION 0\r\n"
        b"REFERENCE 0,0\r\n"
    )
    if header != expected_header:
        raise AssertionError(f"Unexpected TSPL header bytes: {header!r}")

    expected_total_bitmap_bytes = sum(
        bitmap["bitmap_length"] for bitmap in parsed_bitmaps
    )
    return {
        "header": header,
        "bitmap_count": len(parsed_bitmaps),
        "bitmaps": parsed_bitmaps,
        "print_commands": print_commands,
        "expected_labels": sum(print_commands),
        "bitmap_bytes": expected_total_bitmap_bytes,
        "payload_bytes": len(payload),
        "contains_form_feed_command_byte": b"\x0c" in command_bytes,
        "contains_escape_command_byte": b"\x1b" in command_bytes,
        "quantity_matches": print_commands == [expected_quantity],
    }


class DirectPrintDiagnosticsTest(unittest.TestCase):
    def submit_through_mock_spooler(self, quantity: int) -> Dict[str, Any]:
        spooler = MockSpooler(expected_labels=quantity)

        def submit_batch_job(**kwargs: Any) -> Dict[str, Any]:
            kwargs["dry_run"] = False
            kwargs.pop("print_mode")
            return PrinterService.submit_batch_job_raw_tspl(
                **kwargs,
            )

        with contextlib.ExitStack() as stack:
            stack.enter_context(patch.object(printer_service, "HAS_WIN32", True))
            stack.enter_context(patch.object(printer_service, "win32print", spooler, create=True))
            stack.enter_context(patch.object(PrinterService, "validate_printer"))
            stack.enter_context(patch.object(
                PrinterService,
                "submit_batch_job",
                side_effect=submit_batch_job,
            ))

            output = io.StringIO()
            with contextlib.redirect_stderr(output):
                result = print_engine.run_print_job(
                    make_print_job(quantity),
                    dry_run=True,
                )

        return {
            "result": result,
            "spooler": spooler,
            "diagnostic_log": output.getvalue(),
        }

    def test_direct_print_quantities_1_2_and_3_use_one_mocked_spooler_write(self) -> None:
        for quantity in (1, 2, 3):
            with self.subTest(quantity=quantity):
                capture = self.submit_through_mock_spooler(quantity)
                spooler = capture["spooler"]
                analysis = inspect_tspl_payload(spooler.payload, quantity)

                self.assertTrue(capture["result"]["success"])
                self.assertEqual(capture["result"]["total_pages"], quantity)
                self.assertEqual(spooler.start_doc_calls, 1)
                self.assertEqual(spooler.write_calls, 1)
                self.assertEqual(spooler.end_doc_calls, 1)
                self.assertEqual(spooler.open_calls, 1)
                self.assertEqual(spooler.close_calls, 1)
                self.assertEqual(spooler.bytes_written, len(spooler.payload))
                self.assertEqual(analysis["bitmap_count"], 1)
                self.assertEqual(analysis["print_commands"], [quantity])
                self.assertEqual(analysis["expected_labels"], quantity)
                self.assertFalse(analysis["contains_form_feed_command_byte"])
                self.assertFalse(analysis["contains_escape_command_byte"])
                self.assertIn(f"job_id={spooler.job_id}", capture["diagnostic_log"])
                self.assertIn(f"job_bytes={len(spooler.payload)}", capture["diagnostic_log"])
                self.assertIn(f"bytes_written={spooler.bytes_written}", capture["diagnostic_log"])
                self.assertIn(f"expected_labels={quantity}", capture["diagnostic_log"])

                print(
                    f"qty={quantity}: job_id={spooler.job_id}, "
                    f"job_bytes={len(spooler.payload)}, "
                    f"bytes_written={spooler.bytes_written}, "
                    f"expected_labels={analysis['expected_labels']}, "
                    f"bitmap={analysis['bitmaps'][0]['width_bytes']}B x "
                    f"{analysis['bitmaps'][0]['height_px']} dots "
                    f"({analysis['bitmap_bytes']} bytes), "
                    f"PRINT commands={analysis['print_commands']}, "
                    "WritePrinter calls=1 (mock)"
                )

    def test_payload_analysis_rejects_extra_commands_and_unexpected_bytes(self) -> None:
        capture = self.submit_through_mock_spooler(2)
        analysis = inspect_tspl_payload(capture["spooler"].payload, 2)

        self.assertTrue(analysis["quantity_matches"])
        self.assertEqual(analysis["bitmap_bytes"], 38 * 120)
        self.assertEqual(analysis["payload_bytes"], len(capture["spooler"].payload))
        self.assertEqual(capture["spooler"].payload.count(b"CLS\r\nBITMAP "), 1)
        self.assertEqual(capture["spooler"].payload.count(b"PRINT 1,"), 1)

        with self.assertRaisesRegex(AssertionError, "Unexpected bytes after PRINT command"):
            inspect_tspl_payload(capture["spooler"].payload + b"\x0c", 2)


if __name__ == "__main__":
    unittest.main(verbosity=2)
