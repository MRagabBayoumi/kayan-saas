"""
Measurement units and conversion utilities for thermal printing.
Standard thermal printers (like Xprinter XP-233B) operate at 203 DPI (8 dots/mm).
"""

DPI_DEFAULT = 203


def mm_to_px(mm: float, dpi: int = DPI_DEFAULT) -> int:
    """Convert millimeters to printer pixels based on DPI."""
    return int(round(float(mm) / 25.4 * dpi))


def px_to_mm(px: int, dpi: int = DPI_DEFAULT) -> float:
    """Convert printer pixels to millimeters based on DPI."""
    return round(float(px) * 25.4 / dpi, 2)
