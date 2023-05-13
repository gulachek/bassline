import * as React from 'react';
import {
	useState,
	useCallback,
	useEffect,
	useMemo,
	useReducer,
	useRef,
	ChangeEvent,
	KeyboardEvent,
	MouseEvent,
	MutableRefObject
} from 'react';

import { createRoot } from 'react-dom/client';
import { useElemState } from './useElemState';

import { AuthPluginUserEditComponent } from './authPluginUserEdit';

import './siwg_edit.scss';

type SiwgData = string[];

interface IEmailArrayState
{
	emails: string[];
	validity: boolean[];
	index: number;
}

interface IEmailUpdateAction
{
	type: 'update';
	value: string;
	isValid: boolean;
}

interface IEmailAddAction
{
	type: 'add';
}

interface IEmailRemoveAction
{
	type: 'remove';
}

interface IEmailCycleAction
{
	type: 'cycle';
	offset: number;
}

interface IEmailSelectAction
{
	type: 'select';
	index: number;
}

type IEmailAction = IEmailUpdateAction
	| IEmailAddAction 
	| IEmailRemoveAction
	| IEmailCycleAction
	| IEmailSelectAction;

function emailArrayReducer(state: IEmailArrayState, action: IEmailAction)
{
	const emails = [...state.emails];
	const validity = [...state.validity];
	let index = state.index;

	if (action.type === 'update')
	{
		const { value, isValid } = action;
		emails[index] = value;
		validity[index] = isValid;
	}
	else if (action.type === 'add')
	{
		emails.push('');
		validity.push(false); // empty email is invalid
		index = emails.length - 1;
	}
	else if (action.type === 'remove')
	{
		emails.splice(index, 1);
		validity.splice(index, 1);
	}
	else if (action.type === 'cycle')
	{
		const { offset } = action;
		index += offset + emails.length;
		index %= emails.length;
	}
	else if (action.type === 'select')
	{
		index = action.index;
	}
	else
	{
		throw new Error(`Invalid action type`);
	}

	index = Math.max(index, 0);
	index = Math.min(index, emails.length - 1);
	return { emails, index, validity };
}

function arrayEqual(left: string[], right: string[])
{
	if (left.length !== right.length)
		return false;

	for (let i = 0; i < left.length; ++i)
		if (left[i] !== right[i])
			return false;

	return true;
}

export const UserEditor: AuthPluginUserEditComponent<SiwgData> = (props) =>
{
	const { data, setData } = props;

	const initValid = data.map((e: any) => true);
	const initialState = { emails: data, index: 0, validity: initValid };

	const [state, dispatch] = useReducer(emailArrayReducer, initialState);
	const { emails, index, validity } = state;

	const allValid = validity.every(e => e);
	useEffect(() => setData(emails, allValid), [emails, allValid]);

	const onChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		dispatch({
			type: 'update',
			value: e.target.value,
			isValid: e.target.reportValidity()
		});
	}, []);

	const keyDown = useCallback((e: KeyboardEvent) => {
		if (e.key === 'ArrowDown')
		{
			dispatch({ type: 'cycle', offset: +1 });
		}
		else if (e.key === 'ArrowUp')
		{
			dispatch({ type: 'cycle', offset: -1 });
		}
	}, []);

	const clickAdd = useCallback((e: MouseEvent) => {
		e.preventDefault(); // no form submission
		dispatch({ type: 'add' });
	}, []);

	const clickRemove = useCallback((e: MouseEvent) => {
		e.preventDefault(); // no form submission
		dispatch({ type: 'remove' });
	}, []);

	const items = emails.map((email: string, i: number) => {
		let classNames = 'cell';

		if (i === index)
			classNames += ' selected';

		if (!validity[i])
			classNames += ' invalid';

		return <button
			key={i}
			className={classNames}
			tabIndex={-1}
			onClick={() => dispatch({ type: 'select', index: i })}
		>
			{emails[i] || '(empty)'}
		</button>;
	});

	let value = '';
	if (emails.length && index < emails.length)
		value = emails[index];

	const inputRef = useRef<HTMLInputElement>();
	useEffect(() => {
		// when we rerender, just do this
		inputRef.current.reportValidity();
	});

	return <div className="siwg" onKeyDown={keyDown}>
		<div className="controls">
			<input ref={inputRef} type="email"
				required={!!emails.length}
				maxLength={128}
				onChange={onChange}
				value={value}
			/>
			<button onClick={clickAdd}> + </button>
			<button onClick={clickRemove}> - </button>
		</div>
		<div className="array">
			{items}
		</div>
	</div>;
};

export function modelEquals(left: SiwgData, right: SiwgData): boolean
{
	return arrayEqual(left, right);
}
