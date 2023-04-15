import * as React from 'react';
import {
	useCallback,
	ChangeEvent
} from 'react';

import { AuthPluginConfigEditComponent } from '../authConfigEdit/authPluginConfigEdit';

interface IData
{
	enabled: boolean;
}

export const ConfigEditor: AuthPluginConfigEditComponent = (props) =>
{
	const { data, setData } = props;
	const onChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		setData({ enabled: e.target.checked });
	}, []);

	return <label className="noauth-enabled">
		<input type="checkbox" checked={data.enabled} onChange={onChange} />
		enabled
	</label>;
};

export function modelEquals(a: IData, b: IData)
{
	return a.enabled === b.enabled;
}
