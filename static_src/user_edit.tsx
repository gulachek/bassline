import * as React from 'react';
import {
	useRef,
	useEffect,
	useCallback,
	useState,
	FormEvent
} from 'react';

import { useElemState } from './useElemState';
import { renderReactPage } from './renderReactPage';
import { postJson } from './postJson';

interface ISaveReponse
{
	errorMsg?: string|null;
}

function ModalErrorMsg(props: {msg: string|null})
{
	const dialogRef = useRef<HTMLDialogElement>();
	const { msg } = props;

	useEffect(() => {

		if (msg)
			dialogRef.current.showModal();
		else
			dialogRef.current.close();

	}, [dialogRef.current, msg]);

	return <dialog ref={dialogRef}>
		<h2> Error </h2>
		<p> {msg} </p>
		<form method="dialog">
			<button> Ok </button>
		</form>
	</dialog>;
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
	const { user, patterns } = props;

	const [errorMsg, setErrorMsg] = useState(props.errorMsg);

	const [uname, unameOnChange] = useElemState(user.username);
	const [savedUname, setSavedUname] = useState(user.username);

	const userId = user.id;

	const formData = useRef({
		username: uname,
		user_id: userId
	});

	// keep form data in sync
	useEffect(() => {
		formData.current.username = uname;
	}, [uname]);

	const submitForm = useCallback(async (e: FormEvent) => {
		e.preventDefault(); // don't actually navigate page

		const submittedData = { ...formData.current };
		
		const { errorMsg } = await postJson<ISaveReponse>('/site/admin/users', {
			body: submittedData,
			query: {
				action: 'save'
			}
		});

		setErrorMsg(errorMsg);

		if (!errorMsg)
			setSavedUname(submittedData.username);
		
	}, [formData]);

	const hasChanges = uname !== savedUname;

	return <React.Fragment>
		<ModalErrorMsg msg={errorMsg || null} />

		<h1> Edit User </h1>

		<form onSubmit={submitForm}>

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

		<input disabled={!hasChanges} type="submit" name="action" value="Save" />

		</form>
	</React.Fragment>;
}

renderReactPage<IPageProps>(model => <Page {...model} />);
