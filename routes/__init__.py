from flask import Blueprint

auth_bp = Blueprint("auth", __name__, url_prefix="/auth")
admin_bp = Blueprint("admin", __name__, url_prefix="/admin")
candidate_bp = Blueprint("candidate", __name__, url_prefix="/candidate")

# The blueprints are populated in their respective modules to avoid circular imports.
from . import auth_routes, admin_routes, candidate_routes  # noqa: E402,F401


