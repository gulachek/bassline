INSERT INTO system_color (
	id,
	css_name,
	light_css_value,
	dark_css_value,
	default_light_lightness,
	default_dark_lightness,
	description
)
VALUES
( 1, "canvas", "Canvas", "Canvas", 0.9, 0.1,
	"Background of application content or documents."),
( 2, "canvas-text", "CanvasText", "CanvasText", 0.1, 0.9,
	"Text in application content or documents."),
( 3, "link-text", "LinkText", "LinkText", 0.3, 0.7,
	"Text in non-active, non-visited links. For light backgrounds, traditionally blue."),
( 4, "visited-text", "VisitedText", "VisitedText", 0.4, 0.6,
	"Text in visited links. For light backgrounds, traditionally purple."),
( 5, "active-text", "ActiveText", "ActiveText", 0.35, 0.65,
	"Text in active links. For light backgrounds, traditionally red."),
( 6, "button-face", "ButtonFace", "ButtonFace", 0.5, 0.5,
	"The face background color for push buttons."),
( 7, "button-text", "ButtonText", "ButtonText", 0.1, 0.9,
	"Text on push buttons."),
( 8, "button-border", "ButtonBorder", "ButtonBorder", 0.1, 0.9,
	"The base border color for push buttons."),
( 9, "field", "Field", "Field", 0.8, 0.2,
	"Background of input fields."),
(10, "field-text", "FieldText", "FieldText", 0.1, 0.9,
	"Text in input fields."),
(11, "highlight", "Highlight", "Highlight", 0.8, 0.4,
	"Background of selected text, for example from ::selection."),
(12, "highlight-text", "HighlightText", "HighlightText", 0.1, 0.9,
	"Text of selected text."),
(13, "selected-item", "SelectedItem", "SelectedItem", 0.3, 0.8,
	"Background of selected items, for example a selected checkbox."),
(14, "selected-item-text", "SelectedItemText", "SelectedItemText", 0.9, 0.1,
	"Text of selected items."),
(15, "mark", "Mark", "Mark", 0.5, 0.5,
	"Background of text that has been specially marked (such as by the HTML mark element)."),
(16, "mark-text", "MarkText", "MarkText", 0.1, 0.9,
	"Text that has been specially marked (such as by the HTML mark element)."),
(17, "gray-text", "GrayText", "GrayText", 0.1, 0.9,
	"Disabled text. (Often, but not necessarily, gray.)"),
(18, "accent-color", "AccentColor", "AccentColor", 0.3, 0.8,
	"Background of accented user interface controls."),
(19, "accent-color-text", "AccentColorText", "AccentColorText", 0.9, 0.1,
	"Text of accented user interface controls."),
(20, "error", "red", "#ff3838", 0.5, 0.5,
	"Background of error message or foreground of error icon."),
(21, "error-text", "white", "black", 0.9, 0.9,
	"Text of error message."),
(22, "warning", "orange", "#ff9b38", 0.5, 0.5,
	"Background of warning message or foreground of warning icon."),
(23, "warning-text", "white", "black", 0.9, 0.9,
	"Text of warning message."),
(24, "success", "green", "#2cd319", 0.5, 0.5,
	"Background of success message or foreground of success icon."),
(25, "success-text", "white", "black", 0.9, 0.9,
	"Text of success message.")
;