import createClient from 'openapi-fetch';
/**
 * Create a client for Showroom's JSON API.
 *
 *     const showroom = createShowroomClient({ baseUrl: 'https://app.test/showroom' });
 *     const { data } = await showroom.GET('/stats');
 */
export function createShowroomClient({ baseUrl, headers, fetch, credentials }) {
    return createClient({ baseUrl, headers, fetch, credentials });
}
