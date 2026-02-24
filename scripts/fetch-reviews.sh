#!/bin/bash

# Charge les variables depuis .env si présent
if [ -f "$(dirname "$0")/../.env" ]; then
  export $(cat "$(dirname "$0")/../.env" | grep -v '^#' | xargs)
fi

# Vérification des variables requises
if [ -z "$GOOGLE_API_KEY" ]; then
  echo "Erreur : GOOGLE_API_KEY non définie"
  exit 1
fi

if [ -z "$GOOGLE_PLACE_ID" ]; then
  echo "Erreur : GOOGLE_PLACE_ID non définie"
  exit 1
fi

# Chemins
SCRIPT_DIR="$(dirname "$0")"
OUTPUT_FILE="${SCRIPT_DIR}/../data/google_reviews.json"

# Crée le dossier data si nécessaire
mkdir -p "$(dirname "$OUTPUT_FILE")"

# Récupération des avis
echo "Récupération des avis Google..."

curl -s "https://maps.googleapis.com/maps/api/place/details/json?place_id=${GOOGLE_PLACE_ID}&fields=rating,reviews,user_ratings_total&key=${GOOGLE_API_KEY}&language=fr" \
  | jq '{
    rating: .result.rating,
    total_reviews: .result.user_ratings_total,
    reviews: [.result.reviews[]? | {
      author: .author_name,
      rating: .rating,
      text: .text,
      date: .relative_time_description
    }]
  }' > "$OUTPUT_FILE"

# Vérification
if [ -s "$OUTPUT_FILE" ]; then
  REVIEW_COUNT=$(jq '.reviews | length' "$OUTPUT_FILE")
  RATING=$(jq '.rating' "$OUTPUT_FILE")
  echo "✓ ${REVIEW_COUNT} avis récupérés (note : ${RATING}/5)"
  echo "✓ Fichier : ${OUTPUT_FILE}"
else
  echo "✗ Erreur lors de la récupération des avis"
  exit 1
fi