"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const interviewController_1 = require("../controllers/interviewController");
const router = (0, express_1.Router)();
router.post('/join-token', interviewController_1.createJoinToken);
exports.default = router;
//# sourceMappingURL=interviewRoutes.js.map