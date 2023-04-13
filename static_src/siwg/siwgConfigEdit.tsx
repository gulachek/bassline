import * as React from 'react';
import {
	useCallback,
	ChangeEvent
} from 'react';

import { AuthPluginConfigEditComponent } from '../authConfigEdit/authPluginConfigEdit';
import { Checkbox } from '../cbox';

interface IData
{
	enabled: boolean;
	clientId: string;
}

export const ConfigEditor: AuthPluginConfigEditComponent = (props) =>
{
	const { data, setData } = props;
	const { enabled, clientId } = data;
	const onCboxChange = useCallback((checked: boolean) => {
	setData({ enabled: checked, clientId });
	}, [clientId]);

	const onIdChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		setData({ enabled, clientId: e.target.value });
	}, [enabled]);

	return <div>
		<label className="siwg-enabled">
		<Checkbox checked={data.enabled} onChange={onCboxChange} />
		enabled
		</label> <br />
		<label>
		Client ID:
		<input type="text" value={data.clientId} onChange={onIdChange} />
		</label> <br />
		<p> Find your client ID <a href="https://console.cloud.google.com/apis/credentials"> here </a> </p>
	</div>;
};

export function modelEquals(a: IData, b: IData)
{
	return a.enabled === b.enabled && a.clientId === b.clientId;
}
