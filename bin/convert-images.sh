#!/usr/bin/env bash
#
# convert-images.sh — Génère des variantes .avif et .webp à côté des .jpg/.png
# existants. Le thème lb3 (inc/picture.php) détecte ces fichiers jumeaux au
# rendu et émet un <picture><source type="image/avif">… fallback.
#
# Usage :
#   bash bin/convert-images.sh <fichier-ou-dossier> [qualité_webp] [qualité_avif]
#
# Exemples :
#   # Hero fullscreen (toutes variantes)
#   bash bin/convert-images.sh ../../uploads/2019/04/
#
#   # Vignettes films seulement (taille lb3-film-card)
#   find ../../uploads -name "*-1400x0-*.jpg" -print0 | \
#     xargs -0 -I{} bash bin/convert-images.sh {}
#
#   # Qualités custom (défaut : webp 80, avif q20-30)
#   bash bin/convert-images.sh uploads/ 82 25
#
# Dépendances (au moins une dispo) :
#   - libwebp (cwebp)   — convertit en WebP : brew install webp
#   - libavif (avifenc) — convertit en AVIF : brew install libavif
#   - ImageMagick (magick/convert) — fallback pour les deux : brew install imagemagick
#
# Le script skip toute image dont les deux variantes existent déjà. Safe à
# relancer sans duplication de travail.

set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: $0 <fichier-ou-dossier> [qualité_webp=80] [qualité_avif=25]"
    exit 1
fi

TARGET="$1"
WEBP_Q="${2:-80}"
AVIF_Q="${3:-25}"  # Valeur quantizer min (avifenc : 0=lossless, 63=worst)

have_cwebp="$(command -v cwebp 2>/dev/null || true)"
have_avifenc="$(command -v avifenc 2>/dev/null || true)"
have_magick="$(command -v magick 2>/dev/null || command -v convert 2>/dev/null || true)"

if [[ -z "$have_cwebp" && -z "$have_magick" ]]; then
    echo "Erreur : ni cwebp ni ImageMagick trouvés. Installe avec :"
    echo "  brew install webp"
    echo "  ou brew install imagemagick"
    exit 2
fi
if [[ -z "$have_avifenc" && -z "$have_magick" ]]; then
    echo "Erreur : ni avifenc ni ImageMagick trouvés. Installe avec :"
    echo "  brew install libavif"
    echo "  ou brew install imagemagick"
    exit 2
fi

convert_one() {
    local src="$1"
    local base="${src%.*}"
    local webp="${base}.webp"
    local avif="${base}.avif"

    if [[ -f "$avif" && -f "$webp" ]]; then
        echo "skip  ${src##*/}"
        return 0
    fi

    if [[ ! -f "$webp" ]]; then
        if [[ -n "$have_cwebp" ]]; then
            cwebp -quiet -q "$WEBP_Q" -m 6 "$src" -o "$webp"
        else
            "$have_magick" "$src" -quality "$WEBP_Q" "$webp"
        fi
    fi

    if [[ ! -f "$avif" ]]; then
        if [[ -n "$have_avifenc" ]]; then
            # avifenc : --min/--max pour quantizer Y ; -s 6 = vitesse compromise.
            avifenc --min "$AVIF_Q" --max $((AVIF_Q + 10)) -s 6 "$src" "$avif" > /dev/null 2>&1
        else
            "$have_magick" "$src" -quality "$((100 - AVIF_Q * 2))" "$avif"
        fi
    fi

    local sz_src sz_avif sz_webp
    sz_src=$(wc -c < "$src" | tr -d ' ')
    sz_webp=$(wc -c < "$webp" | tr -d ' ')
    sz_avif=$(wc -c < "$avif" | tr -d ' ')
    printf "ok    %-50s  %7s → webp %7s / avif %7s\n" \
        "${src##*/}" "$(numfmt --to=iec $sz_src 2>/dev/null || echo $sz_src)" \
        "$(numfmt --to=iec $sz_webp 2>/dev/null || echo $sz_webp)" \
        "$(numfmt --to=iec $sz_avif 2>/dev/null || echo $sz_avif)"
}

if [[ -d "$TARGET" ]]; then
    # Recherche récursive des JPG/PNG (insensible à la casse).
    while IFS= read -r -d '' file; do
        convert_one "$file"
    done < <(find "$TARGET" -type f \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" \) -print0)
elif [[ -f "$TARGET" ]]; then
    convert_one "$TARGET"
else
    echo "Erreur : $TARGET n'est ni un fichier ni un dossier."
    exit 3
fi
