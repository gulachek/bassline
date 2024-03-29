import * as React from 'react';
import {
  useReducer,
  createContext,
  useContext,
  useCallback,
  useRef,
  useState,
  useEffect,
  ChangeEvent,
  FormEvent,
  MouseEvent,
  HTMLAttributes,
} from 'react';

import { renderReactPage } from './renderReactPage';
import { postJson } from './postJson';
import { OneVisibleChild } from './containers';
import { AutoSaveForm } from './autosave/AutoSaveForm';
import { SaveIndicator } from './autosave/SaveIndicator';
import { ErrorBanner } from './ErrorBanner';

import './group_edit.scss';

type CapabilityId = number;

interface IGroup {
  id: number;
  groupname: string;
  capabilities: CapabilityId[];
}

interface ICapability {
  id: number;
  app: string;
  name: string;
  description: string;
}

interface IPageModel {
  group: IGroup;
  capabilities: { [appKey: string]: ICapability[] };
  initialSaveKey: string;
  groupnameMaxLen: number;
  groupnamePattern: string;
}

interface IEditState {
  group: IGroup;
  savedGroup: IGroup;
  isSaving: boolean;
  saveKey: string;
  fatalMsg: string | null;
  groupnameIsValid: boolean;
}

const GroupDispatchContext = createContext(null);

interface ISetGroupnameAction {
  type: 'setGroupname';
  value: string;
  isValid: boolean;
}

interface IBeginSaveAction {
  type: 'beginSave';
}

interface ISaveResponse {
  error?: string | null;
  newSaveKey?: string | null;
}

interface ISaveRequest {
  group: IGroup;
  saveKey: string;
}

interface IEndSaveAction {
  type: 'endSave';
  response: ISaveResponse;
}

interface IAddCapabilityAction {
  type: 'addCapability';
  capId: CapabilityId;
}

interface IRemoveCapabilityAction {
  type: 'removeCapability';
  capId: CapabilityId;
}

type EditAction =
  | ISetGroupnameAction
  | IBeginSaveAction
  | IEndSaveAction
  | IAddCapabilityAction
  | IRemoveCapabilityAction;

function reducer(state: IEditState, action: EditAction): IEditState {
  const group = { ...state.group };
  let savedGroup = { ...state.savedGroup };
  let { isSaving, saveKey, fatalMsg, groupnameIsValid } = state;
  const caps = new Set(group.capabilities);

  if (action.type === 'setGroupname') {
    group.groupname = action.value;
    groupnameIsValid = action.isValid;
  } else if (action.type === 'beginSave') {
    isSaving = true;
  } else if (action.type === 'endSave') {
    const { response } = action;
    if (response.error) {
      console.error(response.error);
      fatalMsg = response.error;
    } else {
      savedGroup = { ...group };
      saveKey = response.newSaveKey;
    }

    isSaving = false;
  } else if (action.type === 'addCapability') {
    caps.add(action.capId);
  } else if (action.type === 'removeCapability') {
    caps.delete(action.capId);
  }

  group.capabilities = Array.from(caps);
  return {
    group,
    savedGroup,
    isSaving,
    saveKey,
    fatalMsg,
    groupnameIsValid,
  };
}

function groupsAreEqual(a: IGroup, b: IGroup): boolean {
  if (a.groupname !== b.groupname) return false;

  if (a.capabilities.length !== b.capabilities.length) return false;

  for (const capId of a.capabilities) {
    if (!b.capabilities.includes(capId)) return false;
  }

  return true;
}

interface ICapabilitiesProps {
  groupCapabilities: CapabilityId[];
  allCapabilities: { [appKey: string]: ICapability[] };
}

function Capabilities(props: ICapabilitiesProps) {
  const { groupCapabilities, allCapabilities } = props;

  const apps = Object.keys(allCapabilities);
  if (apps.length < 1) throw new Error('No apps. Something is wrong');

  const firstApp = apps[0];
  const [currentApp, setCurrentApp] = useState(firstApp);

  const appCaps = allCapabilities[currentApp];
  if (appCaps.length < 1)
    throw new Error('App has no capabilities. Something is wrong.');

  const firstCap = appCaps[0];
  const [currentCap, setCurrentCap] = useState(firstCap);

  const dispatch = useContext(GroupDispatchContext);

  const changeCap =
    (capId: CapabilityId) => (e: ChangeEvent<HTMLInputElement>) => {
      if (e.target.checked) {
        dispatch({ type: 'addCapability', capId });
      } else {
        dispatch({ type: 'removeCapability', capId });
      }
    };

  const appOptions = apps.map((appKey: string) => {
    return (
      <option key={appKey} value={appKey}>
        {' '}
        {appKey}{' '}
      </option>
    );
  });

  const appSelect = (
    <select
      onChange={(e: ChangeEvent<HTMLSelectElement>) =>
        setCurrentApp(e.target.value)
      }
    >
      {appOptions}
    </select>
  );

  const capCheckboxes = [];
  for (const app of apps) {
    const caps = allCapabilities[app];
    const inputs = caps.map((cap: ICapability) => {
      return (
        <div key={cap.id}>
          {' '}
          <label title={cap.description}>
            <input
              type="checkbox"
              data-capability={cap.name}
              checked={groupCapabilities.includes(cap.id)}
              onChange={changeCap(cap.id)}
            />{' '}
            {cap.name}
          </label>
        </div>
      );
    });

    capCheckboxes.push(
      <div
        key={app}
        data-app={app}
        className={app === currentApp ? 'ovc-visible' : ''}
      >
        {' '}
        {inputs}{' '}
      </div>
    );
  }

  return (
    <section className="section">
      <h3> Capabilities </h3>
      <div>
        <label> Application: {appSelect} </label>
      </div>
      <fieldset className="cap-select">
        <legend> Capabilities </legend>
        <OneVisibleChild> {capCheckboxes} </OneVisibleChild>
      </fieldset>
    </section>
  );
}

interface IGroupNameProps {
  groupname: string;
  maxLen: number;
  pattern: string;
}

function GroupName(props: IGroupNameProps) {
  const { groupname, maxLen, pattern } = props;

  const dispatch = useContext(GroupDispatchContext);

  const onChangeGroupname = useCallback((e: ChangeEvent<HTMLInputElement>) => {
    dispatch({
      type: 'setGroupname',
      value: e.target.value,
      isValid: e.target.reportValidity(),
    });
  }, []);

  return (
    <label>
      {' '}
      groupname:
      <input
        type="text"
        value={groupname}
        onChange={onChangeGroupname}
        required
        maxLength={maxLen}
        pattern={pattern}
        title="Letters, numbers, and underscores are allowed."
      />
    </label>
  );
}

interface IGroupPropertiesProps {
  groupname: string;
  maxLen: number;
  pattern: string;
}

function GroupProperties(props: IGroupPropertiesProps) {
  const { groupname, maxLen, pattern } = props;

  return (
    <section className="section">
      <h3> Group properties </h3>
      <GroupName groupname={groupname} maxLen={maxLen} pattern={pattern} />
    </section>
  );
}

function Page(props: IPageModel) {
  const initialState: IEditState = {
    group: props.group,
    savedGroup: props.group,
    isSaving: false,
    saveKey: props.initialSaveKey,
    fatalMsg: null,
    groupnameIsValid: true,
  };

  const id = props.group.id;

  const [state, dispatch] = useReducer(reducer, initialState);

  const { isSaving, group, savedGroup, saveKey, fatalMsg, groupnameIsValid } =
    state;

  const hasChange = !groupsAreEqual(group, savedGroup);

  const onSave = useCallback(async () => {
    dispatch({ type: 'beginSave' });

    const request: ISaveRequest = {
      group: structuredClone(group),
      saveKey,
    };

    const response = await postJson<ISaveResponse>('./save', { body: request });

    dispatch({ type: 'endSave', response });
  }, [id, group]);

  const shouldSave = hasChange && !fatalMsg && groupnameIsValid;

  return (
    <div className="editor">
      {fatalMsg && <ErrorBanner msg={fatalMsg} />}
      <AutoSaveForm onSave={onSave} shouldSave={shouldSave && !isSaving} />
      <GroupDispatchContext.Provider value={dispatch}>
        <div className="header">
          <h1> Edit group </h1>
        </div>

        <div className="section-container">
          <GroupProperties
            groupname={group.groupname}
            maxLen={props.groupnameMaxLen}
            pattern={props.groupnamePattern}
          />

          <Capabilities
            allCapabilities={props.capabilities}
            groupCapabilities={group.capabilities}
          />
        </div>

        <p className="status-bar">
          <SaveIndicator
            isSaving={shouldSave || isSaving}
            hasError={!!fatalMsg || !groupnameIsValid}
          />
        </p>
      </GroupDispatchContext.Provider>
    </div>
  );
}

renderReactPage<IPageModel>((model) => <Page {...model} />);
