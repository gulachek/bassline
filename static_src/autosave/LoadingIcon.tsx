import * as React from 'react';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
	faCircleXmark,
	faSpinner,
	faCircleCheck
} from '@fortawesome/free-solid-svg-icons';

interface ILoadingIconProps
{
	isLoading: boolean;
	hasError: boolean;
}

export function LoadingIcon(props: ILoadingIconProps)
{
	const { isLoading, hasError } = props;

	let icon = null;

	if (isLoading)
		icon = <FontAwesomeIcon icon={faSpinner} spin />;
	else if (hasError)
		icon = <FontAwesomeIcon icon={faCircleXmark} />;
	else
		icon = <FontAwesomeIcon icon={faCircleCheck} />;

	return <span style={{
		display: 'inline-block',
		width: '1em',
		height: '1em',
		padding: '0 0.25em'
		}}>
		{icon}
	</span>;
}
