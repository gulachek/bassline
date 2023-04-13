import * as React from 'react';
import {
	useCallback,
	ChangeEvent
} from 'react';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

import {
	faSquareCheck
} from '@fortawesome/free-solid-svg-icons';

import {
	faSquare
} from '@fortawesome/free-regular-svg-icons';

import './cbox.scss';

interface ICheckboxProps
{
	checked: boolean;
	disabled?: boolean;
	onChange(checked: boolean): any;
}

export function Checkbox(props: ICheckboxProps)
{
	const { checked, onChange, disabled } = props;

	let icon = checked ? faSquareCheck : faSquare;

	const onCboxChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		onChange(e.target.checked);
	}, [onChange]);

	return <span className="themed-cbox">
		<input type="checkbox" disabled={disabled} checked={checked} onChange={onCboxChange} />
		<FontAwesomeIcon className="cbox-icon" icon={icon} />
	</span>;
}
