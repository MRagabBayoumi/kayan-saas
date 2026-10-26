"""
Font loading and caching helper for thermal label printing.
Loads Windows system TrueType fonts with graceful fallback.
"""

import os
from PIL import ImageFont

_FONT_CACHE = {}

_DEFAULT_WINDOWS_FONT_DIR = r"C:\Windows\Fonts"

_FONT_CANDIDATES = {
    'arabic_bold': [
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "tahomabd.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "arialbd.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "tahoma.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "arial.ttf"),
    ],
    'arabic_regular': [
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "tahoma.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "arial.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "segoeui.ttf"),
    ],
    'details_bold': [
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "arialbd.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "tahomabd.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "arial.ttf"),
    ],
    'details': [
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "arial.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "tahoma.ttf"),
    ],
    'monospace': [
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "arialbd.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "cour.ttf"),
        os.path.join(_DEFAULT_WINDOWS_FONT_DIR, "consola.ttf"),
    ],
}


def get_font(font_type: str = 'arabic_bold', size: int = 14) -> ImageFont.FreeTypeFont:
    """
    Get a cached ImageFont instance for the given font role and size.
    Roles: 'arabic_bold', 'arabic_regular', 'monospace'.
    """
    cache_key = (font_type, size)
    if cache_key in _FONT_CACHE:
        return _FONT_CACHE[cache_key]

    candidates = _FONT_CANDIDATES.get(font_type, _FONT_CANDIDATES['arabic_bold'])

    for font_path in candidates:
        if os.path.exists(font_path):
            try:
                font = ImageFont.truetype(font_path, size=size)
                _FONT_CACHE[cache_key] = font
                return font
            except Exception:
                continue

    # Fallback to default PIL font if none of the candidate files can be loaded
    font = ImageFont.load_default()
    _FONT_CACHE[cache_key] = font
    return font
