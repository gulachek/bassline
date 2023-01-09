import {
	useCallback,
	useState,
	ChangeEvent,
	ChangeEventHandler
} from 'react';

type TValue = string|number|readonly string[];

interface IHasValue {
	value: TValue;
}

export function useElemState<TElem extends HTMLElement & IHasValue>(initialValue: TValue): [TValue, ChangeEventHandler<TElem>]
{
	type TEvent = ChangeEvent<TElem>;
	type THandler = ChangeEventHandler<TElem>;

	const [value, setValue] = useState(initialValue);
	
	const onChange: THandler = useCallback((e: TEvent) => {
		setValue(e.target.value);
	}, []);

	return [value, onChange];
}

