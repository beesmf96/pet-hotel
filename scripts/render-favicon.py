"""Rebuild public/favicon.ico and public/apple-touch-icon.png.

public/favicon.svg is the source of truth. The shapes below mirror it by
hand, in the same 64-unit space, because no SVG rasteriser is available:
this script uses the standard library only. 4x4 supersampling does the
anti-aliasing.

Edit the SVG and these shapes together, then run:

    python3 scripts/render-favicon.py
"""
import os
import struct
import zlib

TEAL = (0x0F, 0x76, 0x6E)
CREAM = (0xFF, 0xF8, 0xE7)

RADIUS = 14.0          # rect rx
PAW = [                # cx, cy, rx, ry
    (14.0, 20.0, 7.0, 8.5),
    (32.0, 18.5, 7.0, 9.0),
    (50.0, 20.0, 7.0, 8.5),
    (32.0, 44.0, 14.5, 10.5),
]
SS = 4                 # supersample factor per axis


def in_rounded_rect(x, y):
    cx = min(max(x, RADIUS), 64.0 - RADIUS)
    cy = min(max(y, RADIUS), 64.0 - RADIUS)
    dx, dy = x - cx, y - cy
    return dx * dx + dy * dy <= RADIUS * RADIUS


def in_paw(x, y):
    for ex, ey, rx, ry in PAW:
        dx, dy = (x - ex) / rx, (y - ey) / ry
        if dx * dx + dy * dy <= 1.0:
            return True
    return False


def render(size, rounded=True):
    """Return RGBA rows. rounded=False fills the square (iOS masks its own)."""
    rows = []
    step = 64.0 / (size * SS)
    for py in range(size):
        row = bytearray()
        for px in range(size):
            bg = paw = 0
            for sy in range(SS):
                y = (py * SS + sy + 0.5) * step
                for sx in range(SS):
                    x = (px * SS + sx + 0.5) * step
                    if not rounded or in_rounded_rect(x, y):
                        bg += 1
                        if in_paw(x, y):
                            paw += 1
            total = SS * SS
            if bg == 0:
                row += bytes(4)
                continue
            # Cream over teal, weighted by how much of the pixel the paw covers.
            t = paw / bg
            row += bytes(
                round(TEAL[i] * (1 - t) + CREAM[i] * t) for i in range(3)
            ) + bytes([round(255 * bg / total)])
        rows.append(bytes(row))
    return rows


def png(size, rows):
    raw = b"".join(b"\x00" + r for r in rows)

    def chunk(tag, data):
        body = tag + data
        return struct.pack(">I", len(data)) + body + struct.pack(">I", zlib.crc32(body))

    return (
        b"\x89PNG\r\n\x1a\n"
        + chunk(b"IHDR", struct.pack(">IIBBBBB", size, size, 8, 6, 0, 0, 0))
        + chunk(b"IDAT", zlib.compress(raw, 9))
        + chunk(b"IEND", b"")
    )


def ico(images):
    """images: list of (size, png bytes). ICO may carry PNG payloads directly."""
    head = struct.pack("<HHH", 0, 1, len(images))
    offset = 6 + 16 * len(images)
    entries, blobs = b"", b""
    for size, blob in images:
        entries += struct.pack(
            "<BBBBHHII", size % 256, size % 256, 0, 0, 1, 32, len(blob), offset
        )
        offset += len(blob)
        blobs += blob
    return head + entries + blobs


if __name__ == "__main__":
    pub = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "public", "")
    sizes = [(n, png(n, render(n))) for n in (16, 32, 48)]
    with open(pub + "favicon.ico", "wb") as fh:
        fh.write(ico(sizes))
    with open(pub + "apple-touch-icon.png", "wb") as fh:
        fh.write(png(180, render(180, rounded=False)))
    print("favicon.ico", sum(len(b) for _, b in sizes), "bytes of PNG payload")
