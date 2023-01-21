import * as React from 'react';
import {
	useRef,
	useMemo,
	useEffect,
	useCallback,
	useState,
	useImperativeHandle,
	FormEvent,
	FC,
	ReactNode,
	MutableRefObject
} from 'react';

import { useElemState } from './useElemState';
import { renderReactPage } from './renderReactPage';
import { postJson } from './postJson';
import { requireAsync } from './requireAsync';
import { AuthPluginUserEditComponent } from './authPluginUserEdit';

import './user_edit.scss';

interface ISaveReponse
{
	errorMsg?: string|null;
}

interface IAuthPluginScriptModule
{
	UserEditor: AuthPluginUserEditComponent;
}

function useModule<T>(script: string)
{
	const [mod, setMod] = useState(null);
	useMemo(async () => {
		setMod(await requireAsync<T>(script));
	}, [script]);

	return mod;
}

interface IAuthPluginData
{
	key: string;
	script: string;
	title: string;
	data: any;
}

interface IAuthPluginEditorProps
{
	script: string;
	title: string;
	savedData: any;
	dataRef: MutableRefObject<any>;
	setHasChange(hasChange: boolean): void;
}

const AuthPluginEditor: FC<IAuthPluginEditorProps> = (props, ref) =>
{
	const { script, title, savedData, dataRef } = props;

	const scriptMod = useModule<IAuthPluginScriptModule>(script);
	const EditorFn = scriptMod && scriptMod.UserEditor;
	const editor = EditorFn && <EditorFn
			dataRef={dataRef}
			savedData={savedData}
			setHasChange={props.setHasChange}
	/>;
	
	return <fieldset>
		<legend> {title} </legend>
		{editor}
	</fieldset>;
};

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
	authPlugins: IAuthPluginData[];
}

interface IFormData
{
	username: string;
	user_id: number;
	pluginData: { [key: string]: any };
}

function Page(props: IPageProps)
{
	const { user, patterns, authPlugins } = props;

	const [errorMsg, setErrorMsg] = useState(props.errorMsg);

	const [uname, unameOnChange] = useElemState(user.username);
	const [savedUname, setSavedUname] = useState(user.username);
	const refIsInit = useRef(true);

	const userId = user.id;

	const initFormData: IFormData = {
		username: uname,
		user_id: userId,
		pluginData: {}
	};

	const formData = useRef(initFormData);

	// keep form data in sync
	useEffect(() => {
		formData.current.username = uname;
	}, [uname]);

	let anyHasChange = false;
	const plugins: ReactNode[] = [];
	const setSavedDataMap = useRef(new Map());
	const dataRefMap = useRef(new Map<string, any>());

	for (const p of authPlugins)
	{
		const [hasChange, setHasChange] = useState(false);
		const dataCopy = useMemo(() => structuredClone(p.data), []);
		const [savedData, setSavedData] = useState(dataCopy);
		const dataRef = useRef(p.data);

		useEffect(() => {
			formData.current.pluginData[p.key] = savedData;
		}, [p.key, savedData]);

		if (refIsInit.current)
		{
			setSavedDataMap.current.set(p.key, setSavedData);
			dataRefMap.current.set(p.key, dataRef);
		}

		anyHasChange = anyHasChange || hasChange;
		plugins.push(<AuthPluginEditor
			key={p.key}
			script={p.script}
			title={p.title}
			savedData={savedData}
			dataRef={dataRef}
			setHasChange={setHasChange}
		/>);
	}

	const submitForm = useCallback(async (e: FormEvent) => {
		e.preventDefault(); // don't actually navigate page

		const submittedData = { ...formData.current };

		dataRefMap.current.forEach((dataRef, key) => {
			submittedData.pluginData[key] = dataRef.current;
		});
		
		const { errorMsg } = await postJson<ISaveReponse>('/site/admin/users', {
			body: submittedData,
			query: {
				action: 'save'
			}
		});

		setErrorMsg(errorMsg);

		if (errorMsg)
			return;

		setSavedUname(submittedData.username);
		for (const key in submittedData.pluginData)
		{
			setSavedDataMap.current.get(key)(submittedData.pluginData[key]);
		}
		
	}, []);

	anyHasChange = anyHasChange || uname !== savedUname;

	refIsInit.current = false;

	return <React.Fragment>
		<ModalErrorMsg msg={errorMsg || null} />

		<h1> Edit User </h1>

		<form onSubmit={submitForm}>
		<input
			className="vanish clickable"
			disabled={!anyHasChange}
			type="submit"
			name="action"
			value="Save"
		/>

		<div>
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
		</div>

		{plugins}

		<div>
			<input
				className="clickable"
				disabled={!anyHasChange}
				type="submit"
				name="action"
				value="Save"
			/>
		</div>

		</form>
	</React.Fragment>;
}

renderReactPage<IPageProps>(model => <Page {...model} />);
