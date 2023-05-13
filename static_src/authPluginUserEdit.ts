import { FC } from 'react';

export interface IAuthPluginUserEditProps<TData>
{
	data: any;
	setData(data: any, isValid: boolean): void;
}

export type AuthPluginUserEditComponent<TData = any> = FC<IAuthPluginUserEditProps<TData>>;
