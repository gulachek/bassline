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
}

export const ConfigEditor: AuthPluginConfigEditComponent = (props) =>
{
	const { data, setData } = props;
	const onChange = useCallback((checked: boolean) => {
		setData({ enabled: checked });
	}, []);

	return <label className="noauth-enabled">
		<Checkbox checked={data.enabled} onChange={onChange} />
		enabled
	</label>;
};

export function modelEquals(a: IData, b: IData)
{
	return a.enabled === b.enabled;
}
