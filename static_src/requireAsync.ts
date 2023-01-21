// assume require.js has been included here

export function requireAsync<TMod = any>(script: string): Promise<TMod>
{
	return new Promise((res) => requirejs([script], res));
}
