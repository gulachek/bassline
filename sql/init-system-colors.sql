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
( 1, "canvas", "Canvas", "Canvas", 0.9, 0.06,
	"Background of application content or documents."),
( 2, "canvas-text", "CanvasText", "CanvasText", 0.1, 0.9,
	"Text in application content or documents."),
( 3, "link-text", "LinkText", "#0cb2cc", 0.3, 0.66,
	"Text in non-active, non-visited links. For light backgrounds, traditionally blue."),
( 4, "visited-text", "VisitedText", "#a568c9", 0.4, 0.6,
	"Text in visited links. For light backgrounds, traditionally purple."),
( 5, "active-text", "ActiveText", "#ef6262", 0.35, 0.79,
	"Text in active links. For light backgrounds, traditionally red."),
( 6, "button-face", "ButtonFace", "ButtonFace", 0.5, 0.35,
	"The face background color for push buttons."),
( 7, "button-text", "ButtonText", "ButtonText", 0.1, 0.9,
	"Text on push buttons."),
( 8, "button-border", "ButtonBorder", "ButtonBorder", 0.1, 0.73,
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
(20, "error", "#eb7a7a", "#600e0e", 0.7, 0.22,
	"Background of error message."),
(21, "error-text", "CanvasText", "CanvasText", 0.1, 0.9,
	"Text of error message."),
(22, "warning", "#ebb37a", "#b76b00", 0.7, 0.36,
	"Background of warning message."),
(23, "warning-text", "CanvasText", "CanvasText", 0.1, 0.9,
	"Text of warning message."),
(24, "success", "#93ea7b", "#103108", 0.7, 0.11,
	"Background of success message."),
(25, "success-text", "CanvasText", "CanvasText", 0.1, 0.9,
	"Text of success message.")
;
