type LivekitTokenOptions = {
    roomName: string;
    identity: string;
    metadata?: Record<string, unknown> | string;
    ttl?: number;
};
export declare const createJoinToken: ({ roomName, identity, metadata, ttl, }: LivekitTokenOptions) => Promise<string>;
export {};
//# sourceMappingURL=livekit.d.ts.map