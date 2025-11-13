"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.createJoinToken = void 0;
const interviewService_1 = require("../services/interviewService");
const logger_1 = require("../utils/logger");
const createJoinToken = (req, res) => {
    const { interviewId, participantId, role, displayName } = req.body ?? {};
    if (!interviewId || !participantId || !role || !displayName) {
        return res.status(400).json({ message: 'Missing required fields' });
    }
    try {
        const payload = interviewService_1.interviewService.registerParticipant({
            interviewId,
            participantId,
            role,
            displayName,
        });
        return res.status(201).json(payload);
    }
    catch (error) {
        logger_1.logger.error('Failed to issue join token', { error });
        return res.status(500).json({ message: 'Unable to issue join token' });
    }
};
exports.createJoinToken = createJoinToken;
//# sourceMappingURL=interviewController.js.map