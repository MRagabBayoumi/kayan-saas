"""
Arabic text shaping and bidirectional (RTL) rendering helper.
Ensures Arabic characters are properly connected and read from right to left.
"""

import re

try:
    import arabic_reshaper
    from bidi.algorithm import get_display
    HAS_ARABIC_SUPPORT = True
except ImportError:
    HAS_ARABIC_SUPPORT = False


_ARABIC_PATTERN = re.compile(r'[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]')


def contains_arabic(text: str) -> bool:
    """Check if the string contains any Arabic Unicode characters."""
    if not text:
        return False
    return bool(_ARABIC_PATTERN.search(str(text)))


def shape_arabic(text: str) -> str:
    """
    Reshape Arabic letters (connect isolated glyphs) and apply BiDi algorithm for RTL display.
    If text does not contain Arabic, returns clean string directly.
    """
    if text is None:
        return ""

    text_str = str(text).strip()
    if not text_str:
        return ""

    if not contains_arabic(text_str) or not HAS_ARABIC_SUPPORT:
        return text_str

    try:
        reshaped = arabic_reshaper.reshape(text_str)
        return get_display(reshaped)
    except Exception:
        # Fallback to plain text if shaping encounters unexpected Unicode tokens
        return text_str
