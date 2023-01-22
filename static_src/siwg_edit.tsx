import * as React from 'react';
import {
	useState,
	useCallback,
	useEffect,
	useMemo,
	useReducer,
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
	index: number;
}

interface IEmailUpdateAction
{
	type: 'update';
	value: string;
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
	let index = state.index;

	if (action.type === 'update')
	{
		const { value } = action;
		emails[index] = value;
	}
	else if (action.type === 'add')
	{
		emails.push('');
		index = emails.length - 1;
	}
	else if (action.type === 'remove')
	{
		emails.splice(index, 1);
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
	return { emails, index };
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
	const { dataRef, savedData, setHasChange } = props;
	const { current } = dataRef;

	const initialState = { emails: dataRef.current, index: 0 };

	const [state, dispatch] = useReducer(emailArrayReducer, initialState);

	const { emails, index } = state;

	useEffect(() => {
		dataRef.current = emails;
		setHasChange(!arrayEqual(emails, savedData));
	}, [emails, savedData]);

	const onChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		dispatch({ type: 'update', value: e.target.value });
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
		let classNames = 'cell clickable';

		if (i === index)
			classNames += ' selected';

		return <button
			key={i}
			className={classNames}
			tabIndex={-1}
			onClick={() => dispatch({ type: 'select', index: i })}
		>
			{emails[i] || '(empty)'}
		</button>;
	});

	const value = emails.length > index ? emails[index] : '';

	return <div className="siwg" onKeyDown={keyDown}>
		<div className="controls">
			<input type="email"
				className="editable"
				onChange={onChange}
				value={value}
			/>
			<button className="clickable" onClick={clickAdd}> + </button>
			<button className="clickable" onClick={clickRemove}> - </button>
		</div>
		<div className="array">
			{items}
		</div>
	</div>;
};
