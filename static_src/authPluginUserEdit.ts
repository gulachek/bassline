import { FC, MutableRefObject } from 'react';

export interface IAuthPluginUserEditProps<TData>
{
	savedData: TData;
	dataRef: MutableRefObject<TData>;
	setHasChange(hasChange: boolean): void;
}

export type AuthPluginUserEditComponent<TData = any> = FC<IAuthPluginUserEditProps<TData>>;
