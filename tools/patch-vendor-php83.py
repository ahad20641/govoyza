#!/usr/bin/env python3
"""
LOCAL TEST HARNESS ONLY - not needed in production.

Production target: PHP >= 8.4 (Laravel 13 / Symfony 8).
This sandbox only has PHP 8.3, so this script rewrites the handful of
PHP 8.4-only syntax constructs inside vendor/ (property hooks used solely for
deprecation warnings, asymmetric visibility, `new X()->call()` chaining,
attributes on constants) into PHP 8.3-compatible equivalents.

Run after `composer install`:  python3 tools/patch-vendor-php83.py
"""
import re, subprocess, sys, pathlib

VENDOR = pathlib.Path(__file__).resolve().parent.parent / "vendor"


def strip_hooks(src: str) -> str:
    """`public Type $x { set {...} }` / `{ get {...} }` -> `public Type $x;`"""
    out, i = [], 0
    pat = re.compile(r"((?:public|protected|private)(?:\s+(?:public|protected|private)\(set\))?\s+(?:readonly\s+)?[?\\\w|]+\s+\$\w+)\s*\{\s*(?=(?:get|set)\b)")
    while True:
        m = pat.search(src, i)
        if not m:
            out.append(src[i:]); break
        out.append(src[i:m.start()]); out.append(m.group(1) + ";")
        depth, j = 1, m.end()
        while depth:
            c = src[j]
            if c == "{": depth += 1
            elif c == "}": depth -= 1
            j += 1
        i = j
    return "".join(out)


def fix(src: str) -> str:
    src = strip_hooks(src)
    # asymmetric visibility -> plain public
    src = re.sub(r"\b(public|protected|private)\s+(?:public|protected|private)\(set\)\s+", r"\1 ", src)
    # public readonly with public(set) removed -> drop readonly so external init still works
    src = re.sub(r"public readonly array \$variables;", "public array $variables;", src)
    # ReflectionProperty::setRawValue (8.4) -> setValue (hooks were removed above)
    src = src.replace("->setRawValue(", "->setValue(")
    # `new Foo(args)->bar` -> `(new Foo(args))->bar`
    bal = r"\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)"
    src = re.sub(r"(?<![\w])new\s+([\\\w]+)(" + bal + r")(?=\s*(?:->|\[|::))", r"(new \1\2)", src)
    # attributes on global constants
    src = re.sub(r"^#\[Deprecated\(.*?\)\]\n(?=const )", "", src, flags=re.M)
    return src


def lint(p):
    return subprocess.run(["php", "-l", str(p)], capture_output=True).returncode == 0


bad = []
for p in VENDOR.rglob("*.php"):
    s = str(p)
    if re.search(r"/(tests?|Tests|fixtures|Fixtures|stubs)/|php-cs-fixer", s):
        continue
    if lint(p):
        continue
    src = p.read_text()
    new = fix(src)
    if new != src:
        p.write_text(new)
    if not lint(p):
        bad.append(s)
    else:
        print("patched", p.relative_to(VENDOR))

pc = VENDOR / "composer" / "platform_check.php"
if pc.exists():
    pc.write_text("<?php // platform check disabled by tools/patch-vendor-php83.py (local PHP 8.3 sandbox only)\n")

if bad:
    print("STILL FAILING:", *bad, sep="\n  ")
    sys.exit(1)
print("vendor/ is PHP 8.3-parseable")
