import { AtriumSelect as e } from "@refactor-circus/atrium";
import { UCheckbox as t, UFormField as n, UInput as r } from "@refactor-circus/atrium/ui";
import { Fragment as i, computed as a, createBlock as o, createCommentVNode as s, createElementBlock as c, createElementVNode as l, createVNode as u, defineComponent as d, mergeModels as f, openBlock as p, renderList as m, toDisplayString as h, unref as g, useModel as _, withCtx as v } from "vue";
import { useI18n as y } from "vue-i18n";
// src/components/structure/ChannelFields.vue?vue&type=script&setup=true&lang.ts
var b = { class: "flex flex-col gap-3" }, x = { class: "mb-1 text-xs font-medium uppercase text-muted" }, S = { class: "flex flex-wrap gap-3" }, C = {
	key: 0,
	class: "text-sm text-muted"
}, w = {
	key: 0,
	class: "text-xs text-error"
}, T = { class: "flex flex-wrap items-start gap-3" }, E = /* @__PURE__ */ d({
	__name: "ChannelFields",
	props: /*@__PURE__*/ f({
		locales: {},
		trees: {},
		action: {},
		testid: {}
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(d) {
		let f = _(d, "modelValue"), E = d, { t: D } = y(), O = a(() => E.trees.map((e) => ({
			label: e.code,
			value: e.code
		})));
		function k(e, t) {
			let n = t === !0;
			f.value.locales = n ? [...f.value.locales.filter((t) => t !== e), e] : f.value.locales.filter((t) => t !== e);
		}
		return (a, _) => {
			let y = t, E = r, A = n, j = e;
			return p(), c("div", b, [l("div", null, [
				l("p", x, h(g(D)("showroom.structure.channels.locales")), 1),
				l("div", S, [(p(!0), c(i, null, m(d.locales, (e) => (p(), o(y, {
					key: e.code,
					"model-value": f.value.locales.includes(e.code),
					label: e.code,
					ui: { label: "font-mono text-xs" },
					"data-testid": `${d.testid}-locale-${e.code}`,
					"onUpdate:modelValue": (t) => k(e.code, t)
				}, null, 8, [
					"model-value",
					"label",
					"data-testid",
					"onUpdate:modelValue"
				]))), 128)), d.locales.length === 0 ? (p(), c("span", C, h(g(D)("showroom.structure.channels.no_locales")), 1)) : s("", !0)]),
				d.action.error("locales") ? (p(), c("small", w, h(d.action.error("locales")), 1)) : s("", !0)
			]), l("div", T, [u(A, {
				label: g(D)("showroom.structure.channels.currencies"),
				hint: g(D)("showroom.structure.channels.currencies_hint"),
				error: d.action.error("currencies")
			}, {
				default: v(() => [u(E, {
					modelValue: f.value.currencies,
					"onUpdate:modelValue": _[0] ||= (e) => f.value.currencies = e,
					class: "w-56",
					"data-testid": `${d.testid}-currencies`
				}, null, 8, ["modelValue", "data-testid"])]),
				_: 1
			}, 8, [
				"label",
				"hint",
				"error"
			]), u(A, {
				label: g(D)("showroom.structure.channels.category_tree"),
				error: d.action.error("category_tree")
			}, {
				default: v(() => [u(j, {
					modelValue: f.value.category_tree,
					"onUpdate:modelValue": _[1] ||= (e) => f.value.category_tree = e,
					items: g(O),
					blank: g(D)("showroom.catalog.none"),
					"blank-value": null,
					class: "w-56",
					"data-testid": `${d.testid}-tree`
				}, null, 8, [
					"modelValue",
					"items",
					"blank",
					"data-testid"
				])]),
				_: 1
			}, 8, ["label", "error"])])]);
		};
	}
});
export { E as t };
