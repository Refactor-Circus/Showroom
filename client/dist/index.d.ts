import { type Client } from 'openapi-fetch';
import type { paths } from './schema.js';
export type { components, operations, paths } from './schema.js';
/** A typed fetch client for Showroom's JSON API. */
export type ShowroomClient = Client<paths>;
export interface ShowroomClientOptions {
    /**
     * The URL of the host's `showroom.routes.prefix`, for example
     * `https://app.test/showroom`. Paths are relative to it (`/stats`).
     */
    baseUrl: string;
    /** Headers sent with every request, such as `Authorization`. */
    headers?: HeadersInit;
    /** A fetch implementation; the global `fetch` by default. */
    fetch?: (input: Request) => Promise<Response>;
    /** Whether cookies go with each request, for a session-authenticated host. */
    credentials?: RequestCredentials;
}
/**
 * Create a client for Showroom's JSON API.
 *
 *     const showroom = createShowroomClient({ baseUrl: 'https://app.test/showroom' });
 *     const { data } = await showroom.GET('/stats');
 */
export declare function createShowroomClient({ baseUrl, headers, fetch, credentials }: ShowroomClientOptions): ShowroomClient;
