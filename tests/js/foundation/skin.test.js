import { beforeEach, describe, expect, it, vi } from 'vitest';
import { stage } from '../support/page';

const transport = vi.hoisted(() => ({ post: vi.fn(() => new Promise(() => {})) }));

vi.mock('@/plugins/axios', () => ({ default: transport }));

stage({ routes: { 'preference.skin': 'preference/skin' } });

const { wear } = await import('@/foundation/skin');

const worn = () => document.documentElement.getAttribute('data-skin');

beforeEach(() => {
    document.documentElement.removeAttribute('data-skin');
    transport.post.mockClear();
});

describe('a user changes the skin they are reading in', () => {
    /**
     * The mocked request never settles, so a page that only changes once it has been answered
     * cannot pass this.
     */
    it('is reading in the new skin before the server has been told', () => {
        wear(true);

        expect(worn()).toBe('dark');
    });

    it('is reading plain again when the skin is taken off', () => {
        wear(true);
        wear(false);

        expect(worn()).toBeNull();
    });

    it('goes on wearing it when the message about it never arrives', async () => {
        transport.post.mockImplementationOnce(() => Promise.reject(new Error('offline')));

        wear(true);
        await new Promise((resolve) => setTimeout(resolve, 0));

        expect(worn()).toBe('dark');
    });
});
