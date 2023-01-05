import { createRoot } from 'react-dom/client';
import * as React from 'react';
import {
	useCallback,
	useState,
	useRef,
	useEffect,
	ChangeEvent,
	ChangeEventHandler
} from 'react';

function ModalErrorMsg(props: {msg: string})
{
	const dialogRef = useRef<HTMLDialogElement>();

	useEffect(() => {
		dialogRef.current.showModal();
	}, [dialogRef]);

	return <dialog ref={dialogRef}>
		<h2> Error </h2>
		<p> {props.msg} </p>
		<form method="dialog">
			<button> Ok </button>
		</form>
	</dialog>;
}

type TValue = string|number|readonly string[];

interface IHasValue {
	value: TValue;
}

function useElemState<TElem extends HTMLElement & IHasValue>(initialValue: TValue): [TValue, ChangeEventHandler<TElem>]
{
	type TEvent = ChangeEvent<TElem>;
	type THandler = ChangeEventHandler<TElem>;

	const [value, setValue] = useState(initialValue);
	
	const onChange: THandler = useCallback((e: TEvent) => {
		setValue(e.target.value);
	}, [setValue]);

	return [value, onChange];
}

interface IUser
{
	id: number;
	username: string;
}

interface IPatterns
{
	username: string;
}

interface IPageProps
{
	errorMsg?: string|null;
	user: IUser;
	patterns: IPatterns;
}

function Page(props: IPageProps)
{
	const { user, patterns, errorMsg } = props;

	const [uname, unameOnChange] = useElemState(user.username);

	const err = errorMsg ? <ModalErrorMsg msg={errorMsg} /> : null;

	return <React.Fragment>
		{err}

		<h1> Edit User </h1>

		<form method="POST">

		<input type="hidden"
			name="user_id"
			value={user.id}
			/>

		<label> username:
			<input type="text"
				name="username"
				title="Enter a username (letters, numbers, or underscores)"
				pattern={patterns.username}
				value={uname}
				onChange={unameOnChange}
				required
				/>
		</label>

		<input type="submit" name="action" value="Save" />

		</form>
	</React.Fragment>;
}

window.addEventListener('DOMContentLoaded', () => {
	const data = document.getElementById('page-model');
	let parsedData: IPageProps = {
		user: { id: -1, username: '[error]' },
		patterns: { username: 'x{400}' }
	};

	try {
		parsedData = JSON.parse(data.innerText) as IPageProps;
	} catch (ex) {
		parsedData.errorMsg = ex.message;
	}

	const app = document.getElementById('page-view');
	const root = createRoot(app);
	root.render(<Page {...parsedData} />);
});
