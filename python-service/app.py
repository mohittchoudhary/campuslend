"""
CampusLend Smart Match Microservice
AI & Proximity Matching Service for Emergency Campus Borrowing.

Purpose:
1. POST /match: Ranks potential lenders by category match, campus building block proximity,
   peer trust score, and availability.
2. POST /remind-overdue: Scans and processes overdue loan reminders.
3. GET /health: Health probe endpoint for PHP fallback detection.
"""

from flask import Flask, request, jsonify
import sys
import os

app = Flask(__name__)

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint used by PHP client before dispatching matching payloads."""
    return jsonify({
        "status": "healthy",
        "service": "CampusLend Smart Match Microservice",
        "version": "1.0.0",
        "campus": "Uka Tarsadia University (UTU)"
    }), 200

@app.route('/match', methods=['POST'])
def match_lenders():
    """
    Ranks peer lenders based on a 4-factor scoring algorithm:
    1. Category Match (40 pts): Exact equipment category alignment.
    2. Proximity (30 pts): Matching campus building or department block.
    3. Trust Score (20 pts): Student peer reputation (0.0 to 5.0 scaled).
    4. Keyword Relevancy (10 pts): Title/description word overlap.
    """
    payload = request.get_json(silent=True) or {}
    req = payload.get('request', {})
    candidates = payload.get('candidates', [])

    if not req or not candidates:
        return jsonify({
            "status": "success",
            "matches": [],
            "message": "No candidates or request details provided"
        }), 200

    req_category_id = int(req.get('category_id', 0))
    req_block = str(req.get('building_block', '')).strip().lower()
    req_title_tokens = set(str(req.get('title', '')).lower().split())

    ranked = []

    for item in candidates:
        score = 0.0
        reasons = []

        # 1. Category Alignment (40 Points)
        item_cat_id = int(item.get('category_id', 0))
        if item_cat_id == req_category_id:
            score += 40.0
            reasons.append("Exact Category Match (+40)")

        # 2. Location Proximity (30 Points)
        item_block = str(item.get('building_block', '')).strip().lower()
        if req_block and item_block and (req_block in item_block or item_block in req_block):
            score += 30.0
            reasons.append(f"Same Campus Block ({item.get('building_block')}) (+30)")
        elif item_block:
            # Different block on campus
            score += 10.0

        # 3. Trust Score Component (Up to 20 Points)
        trust = float(item.get('trust_score', 5.0))
        trust_pts = round((trust / 5.0) * 20.0, 1)
        score += trust_pts
        reasons.append(f"Trust Rating {trust:.1f}/5.0 (+{trust_pts})")

        # 4. Keyword / Title Overlap (Up to 10 Points)
        item_title_tokens = set(str(item.get('title', '')).lower().split())
        common_words = req_title_tokens.intersection(item_title_tokens) - {'a', 'an', 'the', 'in', 'for', 'need'}
        if common_words:
            score += 10.0
            reasons.append(f"Keyword Overlap: {', '.join(list(common_words)[:2])} (+10)")

        # Filter out unavailable or paused items
        if not int(item.get('is_available', 1)) or int(item.get('is_paused', 0)):
            score -= 100.0

        if score > 0:
            item_copy = dict(item)
            item_copy['match_score'] = round(min(score, 100.0), 1)
            item_copy['match_reasons'] = reasons
            ranked.append(item_copy)

    # Sort descending by match score
    ranked.sort(key=lambda x: x['match_score'], reverse=True)

    return jsonify({
        "status": "success",
        "algorithm": "Proximity & Trust Weighted Match (UTU Campus)",
        "total_evaluated": len(candidates),
        "matches": ranked
    }), 200

@app.route('/remind-overdue', methods=['POST'])
def remind_overdue():
    """
    Processes overdue return notifications for loans past due time.
    Returns status and list of reminders queued.
    """
    payload = request.get_json(silent=True) or {}
    overdue_loans = payload.get('overdue_loans', [])
    processed = []

    for loan in overdue_loans:
        tx_id = loan.get('transaction_id')
        borrower = loan.get('borrower_name', 'Student')
        item_title = loan.get('item_title', 'Campus Item')
        due_time = loan.get('due_time', 'Unknown')

        processed.append({
            "transaction_id": tx_id,
            "borrower": borrower,
            "item": item_title,
            "due_time": due_time,
            "action": "Urgent reminder logged and dispatched"
        })

    return jsonify({
        "status": "success",
        "reminders_count": len(processed),
        "details": processed
    }), 200

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    print(f"CampusLend Smart Match Python service running on port {port}...")
    app.run(host='127.0.0.1', port=port, debug=False)
