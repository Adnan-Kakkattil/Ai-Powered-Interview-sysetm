"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.createJoinToken = void 0;
const livekit_server_sdk_1 = require("livekit-server-sdk");
const env_1 = require("./env");
const createJoinToken = ({ roomName, identity, metadata, ttl = 3600, }) => {
    const at = new livekit_server_sdk_1.AccessToken(env_1.env.livekit.apiKey, env_1.env.livekit.apiSecret, {
        identity,
        metadata: typeof metadata === 'string' ? metadata : JSON.stringify(metadata ?? {}),
        ttl,
    });
    at.addGrant({
        room: roomName,
        roomJoin: true,
        canPublish: true,
        canSubscribe: true,
        canPublishData: true,
    });
    return at.toJwt();
};
exports.createJoinToken = createJoinToken;
//# sourceMappingURL=livekit.js.map