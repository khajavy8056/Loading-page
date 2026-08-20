#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Apple Star Page Loader — preset generator (v3.3.0).

Generates the 89 NEW SVG/SMIL presets (assets/presets/*.html) that join the
11 original v3.1 presets for a total of 100 loader designs.

Rules every generated preset follows (same contract as the originals):
  * pure inline SVG + SMIL (<animate> / <animateTransform>) — no <style>,
    no CSS @keyframes, no JS -> immune to optimizer plugins
  * placeholders: {{LOGO}} {{TEXT}} {{DIR}} {{TEXT_COLOR}} {{ACCENT}} {{MAINT}}
  * brand text is static (no per-letter animation)
  * unique gradient/filter ids per file

Run:  python3 tools/generate_presets.py
Output: apple-star-page-loader/assets/presets/*.html  (89 files)
        + tools/_registry_snippet.php  (paste into class-aspl-defaults.php)
"""
import os
import re

OUT_DIR = os.path.join(os.path.dirname(__file__), "..", "apple-star-page-loader", "assets", "presets")

ACC = "{{ACCENT}}"
TXT = "{{TEXT}}"
TC  = "{{TEXT_COLOR}}"


def svg_open(w, h, extra=""):
    return (
        '<svg class="asp-icon-svg" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg" '
        'preserveAspectRatio="xMidYMid meet"%s>\n' % (w, h, extra)
    )


def svg_close():
    return "</svg>\n"


def an(attr, values, dur, begin="0s", repeat="indefinite", fill=None, key_splines=None, key_times=None, calc=None):
    if isinstance(dur, (int, float)):
        dur = "%.2fs" % dur
    s = '    <animate attributeName="%s" values="%s" dur="%s" begin="%s" repeatCount="%s"' % (
        attr, values, dur, begin, repeat,
    )
    if fill:
        s += ' fill="%s"' % fill
    if calc:
        s += ' calcMode="%s"' % calc
    if key_splines:
        s += ' keySplines="%s"' % key_splines
    if key_times:
        s += ' keyTimes="%s"' % key_times
    s += "/>"
    return s


def an_tr(typ, values, dur, begin="0s", repeat="indefinite", fill=None):
    if isinstance(dur, (int, float)):
        dur = "%.2fs" % dur
    s = '    <animateTransform attributeName="transform" type="%s" values="%s" dur="%s" begin="%s" repeatCount="%s"' % (
        typ, values, dur, begin, repeat,
    )
    if fill:
        s += ' fill="%s"' % fill
    s += "/>"
    return s


def fade_in(w, h):
    return (
        '  <animate attributeName="opacity" values="0;1" dur="0.3s" fill="freeze"/>\n'
        '  <animateTransform attributeName="transform" type="translate" values="0 8;0 0" dur="0.4s" fill="freeze"/>\n'
    )


def footer_svg():
    return '\n<div class="asp-brand-text" {{DIR}}>{{TEXT}}</div>\n{{MAINT}}\n'


PRESETS = []  # list of dicts: slug, fa, cat, body


def add(slug, fa, cat, body):
    PRESETS.append({"slug": slug, "fa": fa, "cat": cat, "body": body})


# =====================================================================
# 1. rings — concentric pulse rings
# =====================================================================
def gen_rings():
    for n, dur, spd in [
        (3, "2.0s", 0.55),
        (4, "1.8s", 0.45),
        (5, "2.4s", 0.5),
        (6, "2.2s", 0.36),
        (2, "1.6s", 0.8),
    ]:
        slug = "rings_%02d" % (len([p for p in PRESETS if p["cat"] == "rings"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(n):
            col = "#ffffff" if i % 2 == 0 else ACC
            op = 0.85 - i * 0.12
            parts.append(
                '    <circle r="10" fill="none" stroke="%s" stroke-width="2" opacity="%.2f">\n'
                '      %s\n'
                '      %s\n'
                '    </circle>\n'
                % (
                    col, max(0.25, op),
                    an("r", "10;44;10", dur, begin="-%.2fs" % (i * spd)),
                    an("opacity", "%.2f;0;%.2f" % (max(0.25, op), max(0.25, op)), dur, begin="-%.2fs" % (i * spd)),
                )
            )
        parts.append(
            '    <circle r="7" fill="#ffffff">\n'
            '      %s\n'
            '      %s\n'
            '    </circle>\n'
            % (an("r", "6;9;6", "1.1s"), an("opacity", ".85;1;.85", "1.1s"))
        )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "حلقه‌های متحدالمرکز %d" % n, "rings", "".join(parts))


# =====================================================================
# 2. halo — core + expanding halo glow
# =====================================================================
def gen_halo():
    for n, dur, rmax in [(2, 2.2, 40), (3, 1.9, 46), (4, 2.6, 52), (2, 1.5, 34)]:
        slug = "halo_%02d" % (len([p for p in PRESETS if p["cat"] == "halo"]) + 1)
        gid = "hlg%s" % slug
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append(
            '  <defs>\n'
            '    <radialGradient id="%s" cx="50%%" cy="50%%" r="50%%">\n'
            '      <stop offset="0%%" stop-color="%s" stop-opacity=".9"/>\n'
            '      <stop offset="100%%" stop-color="%s" stop-opacity="0"/>\n'
            '    </radialGradient>\n'
            '  </defs>\n' % (gid, ACC, ACC)
        )
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(n):
            parts.append(
                '    <circle r="12" fill="url(#%s)">\n'
                '      %s\n'
                '      %s\n'
                '    </circle>\n'
                % (gid,
                   an("r", "12;%d;12" % rmax, dur, begin="-%.2fs" % (i * dur / n)),
                   an("opacity", ".0;.8;0", dur, begin="-%.2fs" % (i * dur / n)))
            )
        parts.append(
            '    <circle r="9" fill="#ffffff">\n'
            '      %s\n'
            '    </circle>\n' % an("r", "8;11;8", "1.2s")
        )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "هاله نورانی %d" % n, "halo", "".join(parts))


# =====================================================================
# 3. bounce — bouncing dots
# =====================================================================
def gen_bounce():
    counts = [3, 5, 7, 9, 11, 5]
    for c in counts:
        slug = "bounce_%02d" % (len([p for p in PRESETS if p["cat"] == "bounce"]) + 1)
        w = 60 + (c - 1) * 34
        parts = [svg_open(w, 120), fade_in(w, 120)]
        for i in range(c):
            cx = 30 + i * ((w - 60) / (c - 1) if c > 1 else 0)
            parts.append(
                '  <circle cx="%.1f" cy="60" r="7" fill="%s" fill-opacity=".9">\n'
                '    %s\n'
                '    %s\n'
                '  </circle>\n'
                % (cx, "#ffffff" if i % 2 == 0 else ACC,
                   an("cy", "60;34;60", "1.0s", begin="-%.2fs" % (i * 0.09), calc="spline",
                      key_splines="0.45 0 0.55 1;0.45 0 0.55 1", key_times="0;0.5;1"),
                   an("r", "7;5;7", "1.0s", begin="-%.2fs" % (i * 0.09)))
            )
        parts.append(svg_close())
        add(slug, "توپ‌های جهنده %d" % c, "bounce", "".join(parts))


# =====================================================================
# 4. wave — sine wave dots
# =====================================================================
def gen_wave():
    for c, dur, amp in [(7, 1.3, 26), (9, 1.5, 30), (11, 1.8, 34), (13, 2.1, 38), (5, 1.1, 22)]:
        slug = "wave_%02d" % (len([p for p in PRESETS if p["cat"] == "wave"]) + 1)
        w = 60 + (c - 1) * 26
        parts = [svg_open(w, 120), fade_in(w, 120)]
        for i in range(c):
            cx = 30 + i * ((w - 60) / (c - 1) if c > 1 else 0)
            cy0 = 60 - amp + (2 * amp) * (i / (c - 1) if c > 1 else 0.5)
            phase = -i * (dur / c)
            parts.append(
                '  <circle cx="%.1f" cy="%.1f" r="6" fill="%s" fill-opacity=".85">\n'
                '    %s\n'
                '    %s\n'
                '  </circle>\n'
                % (cx, cy0, ACC if i % 2 == 0 else "#ffffff",
                   an("cy", "%.1f;%.1f;%.1f" % (cy0, 60 + (60 - cy0), cy0), dur, begin="%ss" % phase),
                   an("opacity", ".4;1;.4", dur, begin="%ss" % phase))
            )
        parts.append(svg_close())
        add(slug, "موج سینوسی %d" % c, "wave", "".join(parts))


# =====================================================================
# 5. eq — equalizer bars
# =====================================================================
def gen_eq():
    for c, dur in [(5, 1.0), (7, 1.2), (9, 0.9), (11, 1.4), (13, 1.1)]:
        slug = "eq_%02d" % (len([p for p in PRESETS if p["cat"] == "eq"]) + 1)
        w = 40 + c * 20
        bw = 10
        parts = [svg_open(w, 120), fade_in(w, 120)]
        for i in range(c):
            x = 20 + i * ((w - 40) / (c - 1) if c > 1 else 0) - bw / 2
            hmax = 24 + (i % 3) * 14
            hmin = 8 + (i % 2) * 6
            parts.append(
                '  <rect x="%.1f" y="70" width="%d" height="%d" rx="5" fill="%s">\n'
                '    %s\n'
                '    %s\n'
                '  </rect>\n'
                % (x, bw, hmax, ACC if i % 2 == 0 else "#ffffff",
                   an("height", "%d;%d;%d" % (hmin, hmax, hmin), dur, begin="-%.2fs" % (i * dur / c),
                      calc="spline", key_splines="0.45 0 0.55 1;0.45 0 0.55 1", key_times="0;0.5;1"),
                   an("y", "%d;%d;%d" % (70 - hmin, 70 - hmax, 70 - hmin), dur, begin="-%.2fs" % (i * dur / c),
                      calc="spline", key_splines="0.45 0 0.55 1;0.45 0 0.55 1", key_times="0;0.5;1"))
            )
        parts.append(svg_close())
        add(slug, "اکولایزر %d نوار" % c, "eq", "".join(parts))


# =====================================================================
# 6. arc — arc spinners
# =====================================================================
def gen_arc():
    for n, dur, r in [(1, "0.9s", 34), (2, "1.2s", 34), (3, "1.5s", 36), (2, "0.8s", 40), (4, "1.4s", 42)]:
        slug = "arc_%02d" % (len([p for p in PRESETS if p["cat"] == "arc"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(n):
            dash = 55 + (i % 3) * 18
            col = "#ffffff" if i % 2 == 0 else ACC
            op = 1.0 - i * 0.18
            parts.append(
                '    <circle r="%d" fill="none" stroke="%s" stroke-width="4" stroke-linecap="round" stroke-dasharray="%d 400" opacity="%.2f">\n'
                '      %s\n'
                '    </circle>\n'
                % (r + i * 6, col, dash, max(0.35, op),
                   an_tr("rotate", "0;360", dur, begin="%ss" % (-i * 0.2)))
            )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "کمان چرخان %d" % n, "arc", "".join(parts))


# =====================================================================
# 7. atom — electron orbits
# =====================================================================
def gen_atom():
    for n, dur, rx in [(2, 2.4, 46), (3, 3.0, 48), (4, 3.6, 52), (5, 4.2, 56)]:
        slug = "atom_%02d" % (len([p for p in PRESETS if p["cat"] == "atom"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(n):
            ry = 14 + (i % 3) * 5
            rot0 = (360 / n) * i
            parts.append(
                '    <ellipse rx="%d" ry="%d" fill="none" stroke="%s" stroke-width="1.3" stroke-opacity=".55" transform="rotate(%d)"/>\n'
                % (rx, ry, ACC, rot0)
            )
            parts.append(
                '    <g transform="rotate(%d) translate(%d 0)">\n'
                '      <circle r="5" fill="#ffffff">\n'
                '        %s\n'
                '        %s\n'
                '      </circle>\n'
                '    </g>\n'
                % (rot0, rx,
                   an_tr("rotate", "0;360", dur, begin="%ss" % (-i * dur / n)),
                   an("opacity", "1;.55;1", "1.2s", begin="%ss" % (-i * 0.3)))
            )
        parts.append(
            '    <circle r="10" fill="#ffffff">\n'
            '      %s\n'
            '    </circle>\n'
            '    <circle r="5" fill="%s"/>\n' % (an("r", "9;12;9", "1.4s"), ACC)
        )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "اتم مداری %d" % n, "atom", "".join(parts))


# =====================================================================
# 8. radar — radar sweep
# =====================================================================
def gen_radar():
    for rings, dur, sweep in [(3, "2.2s", 360), (4, "2.6s", 360), (3, "1.8s", 180), (5, "3.0s", 360)]:
        slug = "radar_%02d" % (len([p for p in PRESETS if p["cat"] == "radar"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(rings):
            r = 14 + i * 13
            parts.append(
                '    <circle r="%d" fill="none" stroke="rgba(255,255,255,.28)" stroke-width="1"/>' % r
            )
        parts.append(
            '    <circle r="60" fill="none" stroke="%s" stroke-width="1.4" stroke-opacity=".5"/>' % ACC
        )
        parts.append(
            '    <path d="M0,0 L0,-60 A60,60 0 0 1 51.96,-30 Z" fill="%s" fill-opacity=".16">\n'
            '      %s\n'
            '    </path>\n'
            % (ACC, an_tr("rotate", "0;%d" % sweep, dur))
        )
        parts.append(
            '    <circle cx="0" cy="-30" r="4" fill="#ffffff">\n'
            '      %s\n'
            '    </circle>\n'
            % an_tr("rotate", "0;%d" % sweep, dur, begin="0s")
        )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "رادار چرخان %d" % rings, "radar", "".join(parts))


# =====================================================================
# 9. star — twinkling star field
# =====================================================================
def gen_star():
    variants = [
        [(90, 60, 26), (40, 30, 8), (140, 34, 10), (55, 88, 6), (128, 92, 12), (168, 70, 7), (22, 62, 9)],
        [(90, 60, 30), (35, 40, 10), (145, 45, 11), (60, 90, 7), (120, 30, 8), (160, 80, 9), (70, 25, 6)],
        [(90, 60, 22), (50, 30, 12), (130, 30, 9), (40, 85, 10), (145, 85, 8), (75, 92, 6), (112, 90, 11)],
        [(90, 60, 34), (30, 35, 9), (150, 35, 8), (35, 90, 8), (150, 90, 9), (95, 25, 7), (60, 30, 6)],
        [(90, 60, 18), (25, 60, 8), (155, 60, 8), (90, 20, 10), (90, 100, 10), (52, 40, 7), (128, 40, 7), (52, 80, 7), (128, 80, 7)],
    ]
    for idx, stars in enumerate(variants):
        slug = "star_%02d" % (len([p for p in PRESETS if p["cat"] == "star"]) + 1)
        parts = [svg_open(200, 120), fade_in(200, 120)]
        for i, (sx, sy, sr) in enumerate(stars):
            col = "#ffffff" if i % 3 else ACC
            parts.append(
                '  <circle cx="%d" cy="%d" r="%d" fill="%s">\n'
                '    %s\n'
                '  </circle>\n'
                % (sx, sy, sr, col,
                   an("r", "1;%d;1" % sr, "1.6s", begin="-%ss" % (i * 0.14),
                      calc="spline", key_splines="0.45 0 0.55 1;0.45 0 0.55 1", key_times="0;0.5;1"))
            )
        parts.append(svg_close())
        add(slug, "ستاره‌های چشمک‌زن %d" % (idx + 1), "star", "".join(parts))


# =====================================================================
# 10. ecg_line — ECG heartbeat dash-draw
# =====================================================================
def gen_ecg():
    for dur, col_mode, w2 in [("1.6s", 0, 200), ("2.0s", 1, 220), ("1.4s", 2, 240), ("2.4s", 3, 260), ("1.8s", 4, 280)]:
        slug = "ecg_line_%02d" % (len([p for p in PRESETS if p["cat"] == "ecg_line"]) + 1)
        idx = len([p for p in PRESETS if p["cat"] == "ecg_line"]) + 1
        w = w2
        pts = "20,60 150,60 168,30 186,88 204,52 220,60 %d,60" % (w - 20)
        parts = [svg_open(w, 120), fade_in(w, 120)]
        # approximate path length for the dash-draw loop
        plen = (w - 40) + 120
        parts.append(
            '  <polyline points="%s" fill="none" stroke="%s" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" '
            'stroke-dasharray="%d %d">\n'
            '    %s\n'
            '  </polyline>\n'
            % (pts, ACC if col_mode % 2 else "#ffffff", plen, plen,
               an("stroke-dashoffset", "%d;0" % plen, dur))
        )
        parts.append(
            '  <circle r="5" fill="#ffffff">\n'
            '    %s\n'
            '  </circle>\n'
            % an("cx", "20;%d" % (w - 20), dur, begin="0s")
        )
        parts.append(
            '  <circle cx="20" cy="60" r="3" fill="%s"/>\n'
            '  <circle cx="%d" cy="60" r="3" fill="%s"/>\n' % (ACC, w - 20, ACC)
        )
        parts.append(svg_close())
        add(slug, "ضربان قلب ECG %d" % idx, "ecg_line", "".join(parts))


# =====================================================================
# 11. orbit — planets around a sun
# =====================================================================
def gen_orbit():
    for n, dur0 in [(2, 2.4), (3, 3.2), (4, 3.6), (5, 4.0)]:
        slug = "orbit_%02d" % (len([p for p in PRESETS if p["cat"] == "orbit"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(n):
            r = 30 + i * 12
            dur = dur0 + i * 0.6
            col = "#ffffff" if i % 2 else ACC
            pr = 5 + (i % 3)
            parts.append(
                '    <circle r="%d" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="1"/>\n' % r
            )
            parts.append(
                '    <g>\n'
                '      %s\n'
                '      <circle r="%d" fill="%s">\n'
                '        %s\n'
                '      </circle>\n'
                '    </g>\n'
                % (an_tr("rotate", "0;360", dur, begin="%ss" % (-i * dur / n)), pr, col,
                   an("opacity", "1;.6;1", dur, begin="%ss" % (-i * dur / n)))
            )
        parts.append(
            '    <circle r="14" fill="#ffffff">\n'
            '      %s\n'
            '    </circle>\n'
            '    <circle r="6" fill="%s"/>\n' % (an("r", "13;16;13", "1.6s"), ACC)
        )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "سیاره‌های مداری %d" % n, "orbit", "".join(parts))


# =====================================================================
# 12. infinity — infinity loop dash draw
# =====================================================================
def gen_infinity():
    for dur, size, col_mode in [("2.2s", 46, 0), ("2.6s", 52, 1), ("1.8s", 40, 2), ("3.0s", 58, 3)]:
        slug = "infinity_%02d" % (len([p for p in PRESETS if p["cat"] == "infinity"]) + 1)
        parts = [svg_open(220, 120), fade_in(220, 120)]
        d = (
            "M110,60 C110,20 70,20 70,60 C70,100 150,100 150,60 C150,20 110,20 110,60"
        )
        parts.append(
            '  <path d="%s" fill="none" stroke="%s" stroke-width="4" stroke-linecap="round" '
            'stroke-dasharray="380 600">\n'
            '    %s\n'
            '  </path>\n'
            % (d, ACC if col_mode % 2 else "#ffffff", an("stroke-dashoffset", "380;-220", dur))
        )
        parts.append(
            '  <circle r="7" fill="%s">\n'
            '    %s\n'
            '  </circle>\n'
            % ("#ffffff" if col_mode % 2 else ACC,
               an("opacity", "1;.35;1", "1.1s", begin="-0.2s"))
        )
        parts.append(svg_close())
        add(slug, "بی‌نهایت ∞ %d" % (len([p for p in PRESETS if p["cat"] == "infinity"])), "infinity", "".join(parts))


# =====================================================================
# 13. morph — morphing blob / shape
# =====================================================================
def gen_morph():
    for dur, col_mode, rx in [("2.0s", 0, 42), ("2.6s", 1, 48), ("1.6s", 2, 36), ("3.0s", 3, 52), ("2.2s", 4, 44)]:
        slug = "morph_%02d" % (len([p for p in PRESETS if p["cat"] == "morph"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        col = ACC if col_mode % 2 else "#ffffff"
        h0 = 48
        h1 = rx * 1.4
        parts.append(
            '    <rect x="-%d" y="%d" width="%d" height="%d" rx="24" fill="%s" fill-opacity=".92">\n'
            '      %s\n'
            '      %s\n'
            '      %s\n'
            '      %s\n'
            '    </rect>\n'
            % (rx, -h0 / 2, rx * 2, h0, col,
               an("width", "%d;%d;%d" % (rx, rx * 2, rx), dur, begin="0s"),
               an("x", "%d;%d;%d" % (-rx, -rx * 2, -rx), dur, begin="0s"),
               an("height", "%d;%d;%d" % (h0, h1, h0), dur, begin="0s"),
               an("y", "%.1f;%.1f;%.1f" % (-h0 / 2, -h1 / 2, -h0 / 2), dur, begin="0s"),
               )
        )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "مورف شکل %d" % (len([p for p in PRESETS if p["cat"] == "morph"]) + 1), "morph", "".join(parts))


# =====================================================================
# 14. grid — dot matrix wave
# =====================================================================
def gen_grid():
    for cols, rows, dur in [(5, 3, 1.2), (7, 3, 1.4), (6, 4, 1.6), (9, 3, 1.8)]:
        slug = "grid_%02d" % (len([p for p in PRESETS if p["cat"] == "grid"]) + 1)
        w = 40 + (cols - 1) * 24
        h = 40 + (rows - 1) * 24
        parts = [svg_open(w, h), fade_in(w, h)]
        for r in range(rows):
            for c in range(cols):
                cx = 20 + c * ((w - 40) / (cols - 1) if cols > 1 else 0)
                cy = 20 + r * ((h - 40) / (rows - 1) if rows > 1 else 0)
                ph = round(-(r * cols + c) * (dur / (cols * rows)), 2)
                parts.append(
                    '  <circle cx="%.1f" cy="%.1f" r="5" fill="%s">\n'
                    '    %s\n'
                    '    %s\n'
                    '  </circle>\n'
                    % (cx, cy, "#ffffff" if (r + c) % 2 == 0 else ACC,
                       an("r", "2.5;7;2.5", dur, begin="%ss" % ph),
                       an("opacity", ".4;1;.4", dur, begin="%ss" % ph))
                )
        parts.append(svg_close())
        add(slug, "ماتریس نقطه‌ای %dx%d" % (rows, cols), "grid", "".join(parts))


# =====================================================================
# 15. battery — charging battery
# =====================================================================
def gen_battery():
    for dur, col_mode, fill_max in [("1.8s", 0, 100), ("2.2s", 1, 90), ("1.5s", 2, 100), ("2.6s", 3, 80)]:
        slug = "battery_%02d" % (len([p for p in PRESETS if p["cat"] == "battery"]) + 1)
        parts = [svg_open(200, 120), fade_in(200, 120)]
        parts.append('  <rect x="25" y="35" width="150" height="50" rx="10" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="4"/>')
        parts.append('  <rect x="178" y="50" width="12" height="20" rx="3" fill="rgba(255,255,255,.6)"/>')
        parts.append(
            '  <rect x="31" y="41" width="%d" height="38" rx="6" fill="%s" fill-opacity=".9">\n'
            '    %s\n'
            '  </rect>\n'
            % (int(138 * (fill_max / 100.0)), ACC,
               an("opacity", ".25;1;.25", "1.1s"))
        )
        parts.append(
            '  <circle cx="175" cy="60" r="6" fill="#ffffff">\n'
            '    %s\n'
            '  </circle>\n' % an("opacity", ".4;1;.4", "1.1s")
        )
        parts.append(svg_close())
        add(slug, "شارژ باتری %d" % (len([p for p in PRESETS if p["cat"] == "battery"])), "battery", "".join(parts))


# =====================================================================
# 16. clock — spinning clock hands
# =====================================================================
def gen_clock():
    for dur, col_mode, with_ticks in [(2.0, 0, 1), (2.8, 1, 1), (1.6, 2, 0), (3.2, 3, 1)]:
        slug = "clock_%02d" % (len([p for p in PRESETS if p["cat"] == "clock"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        parts.append('    <circle r="56" fill="none" stroke="rgba(255,255,255,.45)" stroke-width="3"/>')
        if with_ticks:
            for i in range(12):
                a = i * 30
                import math
                rad = math.radians(a)
                x1, y1 = 48 * math.cos(rad), 48 * math.sin(rad)
                x2, y2 = 54 * math.cos(rad), 54 * math.sin(rad)
                parts.append(
                    '    <line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="rgba(255,255,255,.5)" stroke-width="2" stroke-linecap="round"/>\n'
                    % (x1, y1, x2, y2)
                )
        parts.append(
            '    <g>\n'
            '      %s\n'
            '      <line x1="0" y1="8" x2="0" y2="-40" stroke="#ffffff" stroke-width="5" stroke-linecap="round"/>\n'
            '    </g>\n'
            % an_tr("rotate", "0;360", dur)
        )
        parts.append(
            '    <g>\n'
            '      %s\n'
            '      <line x1="0" y1="8" x2="0" y2="-26" stroke="%s" stroke-width="4" stroke-linecap="round"/>\n'
            '    </g>\n'
            % (an_tr("rotate", "0;360", dur, begin="-%.2fs" % (dur * 0.9)), ACC)
        )
        parts.append('    <circle r="6" fill="#ffffff"/>')
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "ساعت گردان %d" % (len([p for p in PRESETS if p["cat"] == "clock"])), "clock", "".join(parts))


# =====================================================================
# 17. aperture — camera aperture blades
# =====================================================================
def gen_aperture():
    for n, dur, r in [(6, "4.0s", 46), (8, "5.0s", 50), (6, "3.0s", 42), (8, "6.0s", 54)]:
        slug = "aperture_%02d" % (len([p for p in PRESETS if p["cat"] == "aperture"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(n):
            a0 = (360 / n) * i
            parts.append(
                '    <path d="M0,-%d L%f,-%f L0,0 Z" fill="%s" fill-opacity=".25">\n'
                '      %s\n'
                '    </path>\n'
                % (r, r * 0.55, r * 0.55, ACC if i % 2 else "#ffffff",
                   an_tr("rotate", "%d;%d" % (a0, a0 + 360), dur, begin="0s"))
            )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "دیافراگم دوربین %d" % n, "aperture", "".join(parts))


# =====================================================================
# 18. pinwheel — pinwheel blades
# =====================================================================
def gen_pinwheel():
    for n, dur, col_mode in [(3, "1.6s", 0), (4, "1.9s", 1), (6, "2.2s", 2), (8, "2.6s", 3)]:
        slug = "pinwheel_%02d" % (len([p for p in PRESETS if p["cat"] == "pinwheel"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(n):
            a0 = (360 / n) * i
            col = "#ffffff" if (i % 2 == 0) else ACC
            parts.append(
                '    <g>\n'
                '      %s\n'
                '      <path d="M0,0 L%f,-%d A%f,%f 0 0 1 %f,-%d Z" fill="%s" fill-opacity=".85"/>\n'
                '    </g>\n'
                % (an_tr("rotate", "%d;%d" % (a0, a0 + 360), dur),
                   46 * 0.5, 46, 46 * 0.5, 46 * 0.5, -46 * 0.5, -46, col)
            )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "پروانه چرخان %d" % n, "pinwheel", "".join(parts))


# =====================================================================
# 19. drop — water drop + ripples
# =====================================================================
def gen_drop():
    for dur, col_mode, rmax in [(1.8, 0, 52), (2.2, 1, 58), (1.5, 2, 46), (2.6, 3, 62)]:
        slug = "drop_%02d" % (len([p for p in PRESETS if p["cat"] == "drop"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,78)">\n')
        col = ACC if col_mode % 2 else "#ffffff"
        parts.append(
            '    <path d="M0,-52 C18,-26 26,-8 26,10 A26,26 0 1 1 -26,10 C-26,-8 -18,-26 0,-52 Z" fill="%s" fill-opacity=".95">\n'
            '      %s\n'
            '    </path>\n' % (col, an("opacity", ".8;1;.8", "1.2s"))
        )
        for i in range(3):
            parts.append(
                '    <circle r="8" fill="none" stroke="%s" stroke-width="2" stroke-opacity=".8">\n'
                '      %s\n'
                '      %s\n'
                '    </circle>\n'
                % (col,
                   an("r", "8;%d;8" % rmax, dur, begin="-%ss" % (i * dur / 3)),
                   an("opacity", ".7;0;.7", dur, begin="-%ss" % (i * dur / 3)))
            )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "قطره آب %d" % (len([p for p in PRESETS if p["cat"] == "drop"])), "drop", "".join(parts))


# =====================================================================
# 20. tri — triangle / triquetra orbit
# =====================================================================
def gen_tri():
    for n, dur, r in [(3, 2.0, 48), (4, 2.4, 50), (3, 2.8, 54), (5, 3.2, 56)]:
        slug = "tri_%02d" % (len([p for p in PRESETS if p["cat"] == "tri"]) + 1)
        parts = [svg_open(180, 180), fade_in(180, 180)]
        parts.append('  <g transform="translate(90,90)">\n')
        for i in range(n):
            a0 = (360 / n) * i
            parts.append(
                '    <circle cx="%d" cy="0" r="7" fill="%s">\n'
                '      %s\n'
                '    </circle>\n'
                % (r, "#ffffff" if i % 2 == 0 else ACC,
                   an_tr("rotate", "%d;%d" % (a0, a0 + 360), dur, begin="%ss" % (-i * dur / n)))
            )
        if n == 3:
            parts.append(
                '    <polygon points="0,-42 36,21 -36,21" fill="none" stroke="rgba(255,255,255,.3)" stroke-width="2"/>\n'
            )
        else:
            parts.append('    <circle r="42" fill="none" stroke="rgba(255,255,255,.3)" stroke-width="2"/>\n')
        parts.append(
            '    <circle r="8" fill="#ffffff">\n'
            '      %s\n'
            '    </circle>\n' % an("r", "7;10;7", "1.3s")
        )
        parts.append('  </g>\n')
        parts.append(svg_close())
        add(slug, "مثلث مداری %d" % n, "tri", "".join(parts))


# =====================================================================
def main():
    gen_rings()
    gen_halo()
    gen_bounce()
    gen_wave()
    gen_eq()
    gen_arc()
    gen_atom()
    gen_radar()
    gen_star()
    gen_ecg()
    gen_orbit()
    gen_infinity()
    gen_morph()
    gen_grid()
    gen_battery()
    gen_clock()
    gen_aperture()
    gen_pinwheel()
    gen_drop()
    gen_tri()

    # Give every preset a unique, stable Persian name: category base + ordinal.
    fa_base = {
        "rings": "حلقه‌های متحدالمرکز",
        "halo": "هاله نورانی",
        "bounce": "توپ‌های جهنده",
        "wave": "موج سینوسی",
        "eq": "اکولایزر",
        "arc": "کمان چرخان",
        "atom": "اتم مداری",
        "radar": "رادار چرخان",
        "star": "ستاره‌های چشمک‌زن",
        "ecg_line": "ضربان قلب ECG",
        "orbit": "سیاره‌های مداری",
        "infinity": "بی‌نهایت",
        "morph": "مورف شکل",
        "grid": "ماتریس نقطه‌ای",
        "battery": "شارژ باتری",
        "clock": "ساعت گردان",
        "aperture": "دیافراگم دوربین",
        "pinwheel": "پروانه چرخان",
        "drop": "قطره آب",
        "tri": "مثلث مداری",
    }
    fa_digits = "۰۱۲۳۴۵۶۷۸۹"
    counters = {}
    for p in PRESETS:
        counters[p["cat"]] = counters.get(p["cat"], 0) + 1
        n = counters[p["cat"]]
        if n < len(fa_digits):
            num = fa_digits[n]
        else:
            num = str(n)
        p["fa"] = "%s %s" % (fa_base[p["cat"]], num)

    os.makedirs(OUT_DIR, exist_ok=True)
    written = 0
    for p in PRESETS:
        fname = os.path.join(OUT_DIR, p["slug"] + ".html")
        # {{LOGO}} must be the first line so an uploaded logo renders above
        # the SVG (same contract as the original v3.1 presets).
        content = (
            "<!-- Preset: %s — %s (SMIL-only, v3.3) -->\n{{LOGO}}\n%s"
            % (p["slug"], p["fa"], p["body"])
        ) + footer_svg()
        with open(fname, "w", encoding="utf-8") as f:
            f.write(content)
        written += 1

    # Registry snippet for class-aspl-defaults.php
    lines = ["\t\t// --- 89 new v3.3.0 presets ---"]
    for p in PRESETS:
        lines.append("\t\t'%s' => __( '%s', 'apple-star-loader' )," % (p["slug"], p["fa"]))
    snippet = "\n".join(lines) + "\n"
    os.makedirs(os.path.join(os.path.dirname(__file__)), exist_ok=True)
    with open(os.path.join(os.path.dirname(__file__), "_registry_snippet.php"), "w", encoding="utf-8") as f:
        f.write(snippet)

    # quick sanity check
    bad = []
    for p in PRESETS:
        body = p["body"]
        if "<style" in body.lower():
            bad.append((p["slug"], "style"))
        if "{{TEXT}}" not in body:
            bad.append((p["slug"], "no TEXT"))
        if "<svg" not in body or "</svg>" not in body:
            bad.append((p["slug"], "svg"))
        if "<animate" not in body and "<animateTransform" not in body:
            bad.append((p["slug"], "no anim"))
    print("Wrote %d presets -> %s" % (written, os.path.abspath(OUT_DIR)))
    print("Problems:", bad if bad else "none")
    print("Total registry entries:", len(PRESETS))


if __name__ == "__main__":
    main()
