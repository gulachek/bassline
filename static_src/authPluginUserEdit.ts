import { FC } from 'react';

export interface IAuthPluginUserEditProps<TData>
{
	data: any;
	setData(data: any): void;
}

export type AuthPluginUserEditComponent<TData = any> = FC<IAuthPluginUserEditProps<TData>>;
