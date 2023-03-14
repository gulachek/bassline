import * as React from 'react';
import {
	useReducer,
	createContext,
	useContext,
	useCallback,
	useState,
	useEffect,
	useRef,
	useMemo,
	ChangeEvent
} from 'react';

import { renderReactPage } from '../renderReactPage';
import { postJson } from '../postJson';
import { AutoSaveForm } from '../autosave/AutoSaveForm';
import { SRGB } from '../srgb';

import './themeEdit.scss';

interface IPaletteColor
{
	id: number;
	name: string;
	hex: string;
}

type JsonMap<TValue> = { [key: string]: TValue };
type InputChangeEvent = ChangeEvent<HTMLInputElement>;

interface IEditableMap<TValue>
{
	items: { [key: string]: TValue };
	deletedItems: string[];
	newItems: { [tempKey: string]: TValue };
}

interface IPalettePreview
{
	id: number;
	name: string;
}

interface IPalette extends IPalettePreview
{
	colors: JsonMap<IPaletteColor>;
}

interface IThemeMapping
{
	id: number;
	app: string;
	name: string;
	theme_color: number;
}

interface IThemeColor
{
	id: number;
	name: string;
	bg_color: number;
	bg_lightness: number;
	fg_color: number;
	fg_lightness: number;
}

enum ThemeStatus
{
	light = 'light',
	dark = 'dark',
	inactive = 'inactive'
}

interface ITheme
{
	id: number;
	name: string;
	palette?: IPalette;
	mappings: JsonMap<IThemeMapping>;
	themeColors: JsonMap<IThemeColor>;
}

interface IThemeEdit
{
	id: number;
	name: string;
	themeColors: IEditableMap<IThemeColor>;
	mappings: JsonMap<IThemeMapping>;
}

interface IAppColor
{
	desc: string;
}

type AppColors = JsonMap<IAppColor>;

interface IPageModel
{
	theme: ITheme;
	available_palettes: JsonMap<IPalettePreview>;
	status: ThemeStatus;
	semantic_colors: JsonMap<AppColors>;
}

interface IEditState
{
	theme: IThemeEdit;
	savedTheme: ITheme;
	isSaving: boolean;
	tempIdCounter: number;
	selectedColorId: string;
	changePaletteVisible: boolean;
	status: ThemeStatus;
	savedStatus: ThemeStatus;
	selectedAppName: string;
}

const ThemeDispatchContext = createContext(null);

interface ISetNameAction
{
	type: 'setName';
	value: string;
}

interface IBeginSaveAction
{
	type: 'beginSave';
}

interface ISaveResponse
{
	error?: string|null;
	mappedColors: JsonMap<number>; // tempId -> id after actually creating
}

interface ISaveRequest
{
	theme: IThemeEdit;
	status: ThemeStatus;
}

interface IEndSaveAction
{
	type: 'endSave';
	response: ISaveResponse;
	request: ISaveRequest;
}

interface ISetColorNameAction
{
	type: 'setColorName';
	id: string;
	name: string;
}

interface ISetColorLightnessAction
{
	type: 'setColorLightness';
	id: string;
	lightness: number;
	isBackground: boolean;
}

interface ISetPaletteColorAction
{
	type: 'setPaletteColor';
	id: string;
	paletteColorId: number;
	isBackground: boolean;
}

interface IAddColorAction
{
	type: 'addColor';
}

interface IDeleteColorAction
{
	type: 'deleteColor';
	id: string;
}

interface ISelectColorAction
{
	type: 'selectColor';
	id: string;
}

interface IChangePaletteVisibleAction
{
	type: 'changePaletteVisible';
	visible: boolean;
}

interface IChangeStatusAction
{
	type: 'changeStatus';
	status: ThemeStatus;
}

interface ISelectAppAction
{
	type: 'selectApp';
	app: string;
}

interface IMapColorAction
{
	type: 'mapColor';
	id: string;
	themeColor: number; 
}

type EditAction = ISetNameAction
	| IBeginSaveAction
	| IEndSaveAction
	| ISetColorNameAction
	| ISetColorLightnessAction
	| ISetPaletteColorAction
	| IAddColorAction
	| IDeleteColorAction
	| ISelectColorAction
	| IChangePaletteVisibleAction
	| IChangeStatusAction
	| ISelectAppAction
	| IMapColorAction
;

function reducer(state: IEditState, action: EditAction)
{
	const theme = {...state.theme};
	let savedTheme = { ...state.savedTheme };
	let {
		isSaving,
		tempIdCounter,
		selectedColorId,
		changePaletteVisible,
		status,
		savedStatus,
		selectedAppName
	} = state;

	const findThemeColor = (id: string) => {
		const { items, newItems } = theme.themeColors;
		
		if (id in items) return items[id];
		if (id in newItems) return newItems[id];
		return null;
	};

	if (action.type === 'setName')
	{
		theme.name = action.value;
	}
	else if (action.type === 'beginSave')
	{
		isSaving = true;
	}
	else if (action.type === 'endSave')
	{
		const { request, response } = action;
		if (response.error)
		{
			console.error(response.error);
		}
		else
		{
			savedTheme.name = request.theme.name;
			savedStatus = request.status;

			const { items, newItems } = theme.themeColors;

			for (const tempId in request.theme.themeColors.newItems)
			{
				const mappedId = response.mappedColors[tempId];

				items[mappedId] = newItems[tempId];
				delete newItems[tempId]; 

				if (tempId === selectedColorId)
					selectedColorId = `${mappedId}`;

				request.theme.themeColors.items[mappedId] =
					request.theme.themeColors.newItems[tempId];
			}

			const deletedItems = [];
			const actuallyDeleted = new Set(request.theme.themeColors.deletedItems);
			for (const id of theme.themeColors.deletedItems)
			{
				if (!actuallyDeleted.has(id)) deletedItems.push(id);
			}
			theme.themeColors.deletedItems = deletedItems;

			savedTheme.themeColors = request.theme.themeColors.items;
			savedTheme.mappings = request.theme.mappings;
		}

		isSaving = false;
	}
	else if (action.type === 'setColorName')
	{
		const { id, name } = action;
		findThemeColor(id).name = name;
	}
	else if (action.type === 'setColorLightness')
	{
		const { id, lightness, isBackground } = action;
		const color = findThemeColor(id);
		if (isBackground)
			color.bg_lightness = lightness;
		else
			color.fg_lightness = lightness;
	}
	else if (action.type === 'setPaletteColor')
	{
		const { id, paletteColorId, isBackground } = action;
		const color = findThemeColor(id);
		if (isBackground)
			color.bg_color = paletteColorId;
		else
			color.fg_color = paletteColorId;
	}
	else if (action.type === 'selectColor')
	{
		selectedColorId = action.id;
	}
	else if (action.type === 'addColor')
	{
		const { bg_color, bg_lightness, fg_color, fg_lightness } = 
			findThemeColor(selectedColorId);

		const newColor: IThemeColor = {
			id: -1,
			name: 'New Color',
			fg_color,
			fg_lightness,
			bg_color,
			bg_lightness
		};

		const tempId = `temp${tempIdCounter++}`;
		theme.themeColors.newItems[tempId] = newColor;
		selectedColorId = tempId;
	}
	else if (action.type === 'deleteColor')
	{
		const { id } = action;
		const { items, newItems, deletedItems } = theme.themeColors;
		const allIds = [...Object.keys(items), ...Object.keys(newItems)];
		if (allIds.length > 1)
		{
			if (id === selectedColorId)
			{
				const index = allIds.indexOf(id);
				const prevIndex = Math.max(0, index - 1);
				selectedColorId = allIds[prevIndex];
			}

			if (id in items)
			{
				deletedItems.push(id);
				delete items[id];
			}
			if (id in newItems) delete newItems[id];

			// TODO: only allow mapping to real items (not newItems)
			const replaceId = Object.keys(items).find(elem => elem !== id);
			for (const mappingId in theme.mappings)
			{
				if (theme.mappings[mappingId].theme_color === parseInt(id))
				{
					theme.mappings[mappingId].theme_color = parseInt(replaceId);
				}
			}
		}
	}
	else if (action.type === 'changePaletteVisible')
	{
		changePaletteVisible = action.visible;
	}
	else if (action.type === 'changeStatus')
	{
		status = action.status;
	}
	else if (action.type === 'selectApp')
	{
		selectedAppName = action.app;
	}
	else if (action.type === 'mapColor')
	{
		const { id, themeColor } = action;
		theme.mappings[id].theme_color = themeColor;
	}

	return {
		theme,
		savedTheme,
		isSaving,
		tempIdCounter,
		selectedColorId,
		changePaletteVisible,
		status,
		savedStatus,
		selectedAppName
	};
}

function pageHasChange(state: IEditState): boolean
{
	const {
		theme,
		savedTheme,
		status,
		savedStatus
	} = state;

	if (theme.name !== savedTheme.name)
		return true;

	if (status !== savedStatus)
		return true;

	for (const id in theme.themeColors.items)
	{
		const editColor = theme.themeColors.items[id];
		const saveColor = savedTheme.themeColors[id];
		if (editColor.name !== saveColor.name)
			return true;

		if (editColor.fg_lightness !== saveColor.fg_lightness)
			return true;

		if (editColor.fg_color !== saveColor.fg_color)
			return true;

		if (editColor.bg_lightness !== saveColor.bg_lightness)
			return true;

		if (editColor.bg_color !== saveColor.bg_color)
			return true;
	}

	for (const tempId in theme.themeColors.newItems)
		return true;

	for (const id in theme.themeColors.deletedItems)
		return true;

	for (const id in theme.mappings)
	{
		if (theme.mappings[id].theme_color !== savedTheme.mappings[id].theme_color)
			return true;
	}

	return false;
}

interface IThemeNameProps
{
	name: string;
}

function ThemeName(props: IThemeNameProps)
{
	const { name } = props;

	const dispatch = useContext(ThemeDispatchContext);

	const onChangeName = useCallback((e: InputChangeEvent) => {
		dispatch({ type: 'setName', value: e.target.value });
	}, []);

	return <label> name:
		<input
			type="text"
			className="theme-name"
			value={name}
			onChange={onChangeName}
			/>
	</label>;
}

interface IPaletteChangePopupProps
{
	palettes: JsonMap<IPalettePreview>;
	open: boolean;
	themeId: number;
}

function PaletteChangePopup(props: IPaletteChangePopupProps)
{
	const { palettes, open, themeId } = props;

	const ref = useRef<HTMLDialogElement>(null);
	const dispatch = useContext(ThemeDispatchContext);

	useEffect(() => {
		if (open)
		{
			ref.current.showModal();
			ref.current.onclose = () => {
				dispatch({ type: 'changePaletteVisible', visible: false });
			};
		}
		else
		{
			ref.current.close();
		}
	}, [open]);

	const paletteOptions = [];
	for (const paletteId in palettes)
	{
		paletteOptions.push(<option key={paletteId} value={paletteId}> {palettes[paletteId].name} </option>);
	}

	return <dialog ref={ref}>
		<form method="POST" action="/site/admin/theme/change_palette">
			<input type="hidden" value={themeId} name="theme-id" />
			<h1> Change Palette </h1>
			<p>
				Changing your palette will permanently clear all mapped theme colors for this theme.
			</p>
			<label> Palette:
				<select className="palette-select" name="palette-id">
					{paletteOptions}
				</select>
			</label>
			<br />
			<input type="submit" value="Change Palette" />
		</form>
	</dialog>;
}

interface IThemePropertiesProps
{
	theme: IThemeEdit;
	currentPaletteId: number | null;
	palettes: JsonMap<IPalettePreview>;
	status: ThemeStatus;
}

function ThemeProperties(props: IThemePropertiesProps)
{
	const {
		theme,
		palettes,
		currentPaletteId,
		status
	} = props;

	const { name } = theme;

	const dispatch = useContext(ThemeDispatchContext);

	let currentPaletteName = 'No palette selected';
	if (typeof currentPaletteId === 'number')
		currentPaletteName = palettes[`${currentPaletteId}`].name;

	const changeStatus = useCallback((e: InputChangeEvent) => {
		dispatch({ type: 'changeStatus', status: e.target.value });
	}, []);

	const radios = ['inactive', 'light', 'dark'].map((s) => {
		return <label key={s}>
			<input type="radio" name="theme-status"
				value={s} checked={s === status} onChange={changeStatus}
			/>
			{s}
		</label>;
	});

	return <Section title="Theme properties">
		<ThemeName name={name} />
		<div>
			<label> palette:
			<button
				className="change-palette"
				title="Change palette"
				onClick={() => dispatch({ type: 'changePaletteVisible', visible: true })}
			> {currentPaletteName} </button>
			</label>
		</div>
		<div>
			{radios}
		</div>
	</Section>;
}

interface IThemeColorEditProps
{
	color: IThemeColor;
	id: string;
	selected: boolean;
	palette: Map<number, SRGB>;
	fgColorName: string;
	bgColorName: string;
}

function ThemeColorEdit(props: IThemeColorEditProps)
{
	const { color, id, selected, palette, fgColorName, bgColorName } = props;
	const { name, fg_color, fg_lightness, bg_color, bg_lightness } = color;

	const dispatch = useContext(ThemeDispatchContext);
	const selectColor = useCallback(() => {
		dispatch({ type: 'selectColor', id });
	}, [id]);

	const style = {
		color: withLightness(palette.get(fg_color), fg_lightness).toHex(),
		backgroundColor: withLightness(palette.get(bg_color), bg_lightness).toHex()
	};

	let className = 'theme-color-edit';
	if (selected) className += ' selected';
	return <button
		className={className}
		style={style}
		data-fg_color={fgColorName}
		data-bg_color={bgColorName}
		data-fg_lightness={fg_lightness}
		data-bg_lightness={bg_lightness}
		onClick={selectColor}
	>
		{name}
	</button>;
}

interface IColoredButtonProps
{
	fg: string;
	bg: string;
	text: string;
	onClick(): void;
}

function ColoredButton(props: IColoredButtonProps)
{
	const { fg, bg, text, onClick } = props;
	const ref = useRef<HTMLButtonElement>(null);

	useEffect(() => {
		ref.current.style.color = fg;
		ref.current.style.backgroundColor = bg;
	}, [fg, bg]);
		
	return <button ref={ref} onClick={onClick}> {text} </button>;
}

interface IThemeColorsProps
{
	colors: IEditableMap<IThemeColor>;
	palette: IPalette;
	selectedColorId: string;
}

function ThemeColors(props: IThemeColorsProps)
{
	const { colors, palette, selectedColorId } = props;
	const { items, newItems } = colors;

	const dispatch = useContext(ThemeDispatchContext);

	const setName = useCallback((e: InputChangeEvent) => {
		dispatch({ type: 'setColorName', id: selectedColorId, name: e.target.value });
	}, [selectedColorId]);

	const setBgLightness = useCallback((e: InputChangeEvent) => {
		dispatch({
			type: 'setColorLightness',
			id: selectedColorId,
			lightness: e.target.valueAsNumber,
			isBackground: true
		});
	}, [selectedColorId]);

	const setFgLightness = useCallback((e: InputChangeEvent) => {
		dispatch({
			type: 'setColorLightness',
			id: selectedColorId,
			lightness: e.target.valueAsNumber,
			isBackground: false
		});
	}, [selectedColorId]);

	// make this selenium-testable
	useEffect(() => {
		(window as any)._setThemeColorLightness = (fg: number, bg: number): void => {
			dispatch({
				type: 'setColorLightness',
				id: selectedColorId,
				lightness: fg,
				isBackground: false
			});

			dispatch({
				type: 'setColorLightness',
				id: selectedColorId,
				lightness: bg,
				isBackground: true
			});
		};
	}, [selectedColorId]);

	const addColor = useCallback(() => {
		dispatch({ type: 'addColor' });
	}, []);

	const delColor = useCallback(() => {
		dispatch({ type: 'deleteColor', id: selectedColorId });
	}, [selectedColorId]);

	const paletteSrgb = useMemo(() => {
		const map = new Map<number, SRGB>();
		for (const sid in palette.colors)
		{
			const { id, hex } = palette.colors[sid];
			map.set(id, SRGB.fromHex(hex));
		}
		return map;
	}, [palette]);

	const allItems: [string, IThemeColor][] = [];
	for (const id in items)
	{
		allItems.push([id, items[id]]);
	}
	for (const id in newItems)
	{
		allItems.push([id, newItems[id]]);
	}

	const names = [];
	let selectedColor: IThemeColor = null;
	for (const [id, item] of allItems)
	{
		const selected = id === selectedColorId; 
		if (selected) selectedColor = item;
		names.push(<ThemeColorEdit
			palette={paletteSrgb}
			key={id} color={item} id={id} selected={selected}
			fgColorName={palette.colors[`${item.fg_color}`].name}
			bgColorName={palette.colors[`${item.bg_color}`].name}
			/>);
	}

	const fgSRGB = withLightness(
		SRGB.fromHex(palette.colors[selectedColor.fg_color].hex),
		selectedColor.fg_lightness);
	const fgHex = fgSRGB.toHex();
	
	const bgSRGB = withLightness(
		SRGB.fromHex(palette.colors[selectedColor.bg_color].hex),
		selectedColor.bg_lightness);
	const bgHex = bgSRGB.toHex();

	const fgPalette = [];
	const bgPalette = [];
	for (const paletteColorId in palette.colors)
	{
		const { hex, id, name } = palette.colors[paletteColorId];
		const srgb = SRGB.fromHex(hex);

		const { fg_lightness, bg_lightness } = selectedColor;

		const setColor = (bg: boolean) => () => dispatch({
			type: 'setPaletteColor',
			paletteColorId: id,
			id: selectedColorId,
			isBackground: bg
		});

		const fgName = id === selectedColor.fg_color ? `*${name}` : name;
		const bgName = id === selectedColor.bg_color ? `*${name}` : name;

		fgPalette.push(<ColoredButton key={id} text={fgName}
			onClick={setColor(false)}
			fg={withLightness(srgb, fg_lightness).toHex()} bg={bgHex}/>);
		bgPalette.push(<ColoredButton key={id} text={bgName}
			onClick={setColor(true)}
			bg={withLightness(srgb, bg_lightness).toHex()} fg={fgHex}/>);
	}

	const contrast = 
		Math.round(fgSRGB.contrastRatio(bgSRGB) * 10) / 10;

	return <Section title="Theme colors">
		<div className="fgbg-editors">
		<fieldset className="fg-editor">
			<legend> Foreground </legend>
			<div> {fgPalette} </div>
			<label> lightness:
			<input type="range" min="0" max="1" step="0.01"
				value={selectedColor.fg_lightness}
				onChange={setFgLightness}
			/>
			</label>
		</fieldset>
		<fieldset className="bg-editor">
			<legend> Background </legend>
			<div> {bgPalette} </div>
			<label> lightness:
			<input type="range" min="0" max="1" step="0.01"
				value={selectedColor.bg_lightness}
				onChange={setBgLightness}
			/>
			</label>
		</fieldset>
		</div>
		<div>
			<input type="text" className="current-theme-color-name"
				value={selectedColor.name}
				onChange={setName}
			/>
			<button className="add-color" onClick={addColor}> + </button>
			<button className="del-color" onClick={delColor}> - </button>
			<label>
				contrast:
				<input type="number" readOnly value={contrast} />
			</label>
		</div>
		<div>
			{names}
		</div>
	</Section>;
}

function findMapping(app: string, color: string, mappings: JsonMap<IThemeMapping>): IThemeMapping
{
	for (const id in mappings)
	{
		if (mappings[id].app === app && mappings[id].name === color)
			return mappings[id];
	}

	throw new Error(`color ${app}.${color} not found`);
}

function* emapForEach<TValue>(map: IEditableMap<TValue>): IterableIterator<[string, TValue]>
{
	for (const id in map.items)
		yield [id, map.items[id]];

	for (const id in map.newItems)
		yield [id, map.newItems[id]];
}

interface IThemeMappingsProps
{
	selectedAppName: string;
	appColors: JsonMap<AppColors>;
	mappings: JsonMap<IThemeMapping>;
	themeColors: IEditableMap<IThemeColor>;
}

function ThemeMappings(props: IThemeMappingsProps)
{
	const { selectedAppName, appColors, mappings, themeColors } = props;

	const dispatch = useContext(ThemeDispatchContext);

	const selectApp = useCallback((e: ChangeEvent<HTMLSelectElement>) => {
		dispatch({ type: 'selectApp', app: e.target.value });
	}, []);

	const appOptions = [];
	for (const appKey in appColors)
	{
		appOptions.push(<option key={appKey} value={appKey}> {appKey} </option>);
	}

	const colors = appColors[selectedAppName];
	const rows = [];
	for (const name in colors)
	{
		const mapping = findMapping(selectedAppName, name, mappings);

		const themeColorOpts = [];
		for (const elem of emapForEach(themeColors))
		{
			const [id, item] = elem;
			themeColorOpts.push(<option key={id} value={id}>
				{item.name}
			</option>);
		}

		const selectColor = (e: ChangeEvent<HTMLSelectElement>) => dispatch({
			type: 'mapColor',
			id: mapping.id,
			themeColor: parseInt(e.target.value)
		});

		rows.push(<div key={name}>
			<label>
				<strong title={colors[name].desc}> ⓘ {name}:</strong>
			</label>
			<select
				className="mapping-select"
				data-mapping-name={name}
				value={mapping.theme_color}
				onChange={selectColor}
			>
				{themeColorOpts}
			</select>
		</div>);
	}

	return <Section title="Theme mappings">
		<div>
			<label> app:
			<select
				onChange={selectApp}
				value={selectedAppName}
				className="app-select"
			>
				{appOptions}
			</select>
			</label>
		</div>
		<dl>
			{rows}
		</dl>
	</Section>;
}

function Page(props: IPageModel)
{
	const initialState: IEditState = {
		theme: {
			name: props.theme.name,
			id: props.theme.id,
			themeColors: {
				items: structuredClone(props.theme.themeColors),
				newItems: {},
				deletedItems: []
			},
			mappings: structuredClone(props.theme.mappings)
		},
		savedTheme: props.theme,
		isSaving: false,
		tempIdCounter: 1,
		selectedColorId: Object.keys(props.theme.themeColors)[0],
		changePaletteVisible: false,
		status: props.status,
		savedStatus: props.status,
		selectedAppName: 'shell'
	};

	const [state, dispatch] = useReducer(reducer, initialState);

	const { isSaving, theme, savedTheme, status } = state;

	const hasChange = pageHasChange(state);

	const onSave = useCallback(async () => {
		dispatch({ type: 'beginSave' });

		const request = { theme: structuredClone(theme), status };

		const response = await postJson<ISaveResponse>('./save', { body: request });

		dispatch({ type: 'endSave', response, request });
	}, [theme, status]);

	return <div>
			<ThemeDispatchContext.Provider value={dispatch}>
				<PaletteChangePopup
					palettes={props.available_palettes}
					open={state.changePaletteVisible}
					themeId={theme.id}
				/>
				<AutoSaveForm onSave={onSave} hasChange={hasChange}>
					<div className="header">
						<h1> Edit theme </h1>
						<p className="save-indicator">
						<input type="checkbox" readOnly checked={!hasChange} /> 
						Saved
						</p>
					</div>

					<div className="section-container">
						<ThemeProperties
							theme={theme}
							currentPaletteId={savedTheme.palette?.id}
							palettes={props.available_palettes}
							status={status}
						/>
						<ThemeColors
							colors={theme.themeColors}
							palette={savedTheme.palette}
							selectedColorId={state.selectedColorId}
						/>
						<ThemeMappings
							selectedAppName={state.selectedAppName}
							appColors={props.semantic_colors}
							mappings={theme.mappings}
							themeColors={theme.themeColors}
						/>
					</div>
				</AutoSaveForm>
			</ThemeDispatchContext.Provider>
		</div>;
}

interface ISectionProps
{
	title: string;
}

function Section(props: React.PropsWithChildren<ISectionProps>)
{
	return <section className="section">
		<h3> {props.title} </h3>
		{props.children}
	</section>;
}

function withLightness(color: SRGB, lightness: number)
{
	const [h, s, l] = color.toHSL();
	return SRGB.fromHSL([h, s, lightness]);
}

renderReactPage<IPageModel>(model => <Page {...model} />);