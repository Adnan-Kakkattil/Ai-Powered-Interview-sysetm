from __future__ import annotations

from typing import Any, Dict

import requests
from flask import current_app


class EyeDetectionServiceError(RuntimeError):
    pass


def analyze_frame(image_bytes: bytes) -> Dict[str, Any]:
    """
    Send a frame to the configured eye detection endpoint.

    Returns a dictionary with analysis results. Raises if the request fails.
    """
    endpoint = current_app.config["EYE_DETECTION_ENDPOINT"]
    try:
        response = requests.post(
            endpoint,
            files={"frame": ("frame.jpg", image_bytes, "image/jpeg")},
            timeout=5,
        )
        response.raise_for_status()
        return response.json()
    except requests.RequestException as exc:
        raise EyeDetectionServiceError(str(exc)) from exc


