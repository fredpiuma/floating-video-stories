#!/usr/bin/env bash
# Gera dist/<slug>-<versão>.zip e dist/<slug>.zip só com os arquivos que o WordPress precisa, dentro da pasta <slug>/.
set -euo pipefail
SLUG="floating-video-stories"
MAIN="fred-video-produto.php"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
VERSION="$(sed -n 's/^[ *]*Version: *//p' "$MAIN" | head -1 | tr -d '\r ')"
[ -n "$VERSION" ] || { echo "Versão não encontrada em $MAIN" >&2; exit 1; }

EXCLUDE=(.github tools docs tests dist node_modules CLAUDE.md AGENTS.md GEMINI.md README.md CHANGELOG.md .gitignore .gitattributes)
PATHSPEC=(.)
for p in "${EXCLUDE[@]}"; do PATHSPEC+=(":(exclude)$p"); done

mkdir -p dist
OUT="dist/$SLUG-$VERSION.zip"
rm -f "$OUT" "dist/$SLUG.zip"
git archive --format=zip --prefix="$SLUG/" -o "$OUT" HEAD -- "${PATHSPEC[@]}"
cp "$OUT" "dist/$SLUG.zip"
echo "$OUT"
