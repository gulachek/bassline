import { FC } from 'react';

export interface IAuthPluginConfigEditProps<TData>
{
	data: TData;
	setData(data: TData): void;
}

export type AuthPluginConfigEditComponent<TData = any> = FC<IAuthPluginConfigEditProps<TData>>;
