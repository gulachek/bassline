import {
	useCallback,
	useState,
	ChangeEvent,
	ChangeEventHandler
} from 'react';

type ElemValue = string;

interface IHasValue {
	value: ElemValue;
}

export function useElemState<TElem extends HTMLElement & IHasValue>(initialValue: ElemValue): [ElemValue, ChangeEventHandler<TElem>]
{
	type TEvent = ChangeEvent<TElem>;
	type THandler = ChangeEventHandler<TElem>;

	const [value, setValue] = useState(initialValue);
	
	const onChange: THandler = useCallback((e: TEvent) => {
		setValue(e.target.value);
	}, []);

	return [value, onChange];
}

