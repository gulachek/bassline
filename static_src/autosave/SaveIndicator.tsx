import * as React from 'react';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
	faCircleXmark,
	faSpinner,
	faCircleCheck
} from '@fortawesome/free-solid-svg-icons';

import './SaveIndicator.scss';

interface ISaveIndicatorProps
{
	isSaving: boolean;
	hasError: boolean;
}

export function SaveIndicator(props: ISaveIndicatorProps)
{
	const { isSaving, hasError } = props;

	let icon = null;
	let [savingCls, savedCls, errorCls] = ['hidden', 'hidden', 'hidden'];

	if (isSaving)
	{
		icon = <FontAwesomeIcon icon={faSpinner} spin />;
		savingCls = 'visible';
	}
	else if (hasError)
	{
		icon = <FontAwesomeIcon icon={faCircleXmark} />;
		errorCls = 'visible';
	}
	else
	{
		icon = <FontAwesomeIcon icon={faCircleCheck} />;
		savedCls = 'visible';
	}

	const iconBlock = <span className="icon">
			{icon}
	</span>;

	const msgBlock = <span className="msg"> 
		<span className={savingCls}>
			Saving
		</span>
		<span className={savedCls}>
			Saved
		</span>
		<span className={errorCls}>
			Error
		</span>
	</span>;

	return <span className="save-indicator">
		{iconBlock} {msgBlock}
	</span>;
}
