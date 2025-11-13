"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.interviewService = void 0;
const livekit_1 = require("../config/livekit");
const roomService_1 = require("./roomService");
const logger_1 = require("../utils/logger");
exports.interviewService = {
    registerParticipant({ interviewId, participantId, role, displayName }) {
        const roomName = roomService_1.roomService.assignLivekitRoom(interviewId);
        const identity = `${role}-${participantId}`;
        const token = (0, livekit_1.createJoinToken)({
            roomName,
            identity,
            metadata: { interviewId, role, displayName },
        });
        logger_1.logger.info('Issued LiveKit token', { interviewId, role });
        return { token, roomName };
    },
};
//# sourceMappingURL=interviewService.js.map