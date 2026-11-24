import { UFormField as e, UInput as t } from "@refactor-circus/atrium/ui";
import { Fragment as n, createCommentVNode as r, createElementBlock as i, createElementVNode as a, createVNode as o, defineComponent as s, mergeModels as c, openBlock as l, renderList as u, toDisplayString as d, unref as f, useModel as p, vModelSelect as m, withCtx as h, withDirectives as g } from "vue";
import { useI18n as _ } from "vue-i18n";
// src/components/structure/ChannelFields.vue?vue&type=script&setup=true&lang.ts
var v = { class: "flex flex-col gap-3" }, y = { class: "mb-1 text-xs font-medium uppercase text-muted" }, b = { class: "flex flex-wrap gap-3" }, x = [
	"checked",
	"data-testid",
	"onChange"
], S = { class: "font-mono text-xs" }, C = {
	key: 0,
	class: "text-sm text-muted"
}, w = {
	key: 0,
	class: "text-xs text-error"
}, T = { class: "flex flex-wrap items-end gap-3" }, E = ["data-testid"], D = { value: "" }, O = ["value"], k = /* @__PURE__ */ s({
	__name: "ChannelFields",
	props: /*@__PURE__*/ c({
		locales: {},
		trees: {},
		action: {},
		testid: {}
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(s) {
		let c = p(s, "modelValue"), { t: k } = _();
		function A(e, t) {
			let n = t.target.checked;
			c.value.locales = n ? [...c.value.locales.filter((t) => t !== e), e] : c.value.locales.filter((t) => t !== e);
		}
		return (p, _) => {
			let j = t, M = e;
			return l(), i("div", v, [a("div", null, [
				a("p", y, d(f(k)("showroom.structure.channels.locales")), 1),
				a("div", b, [(l(!0), i(n, null, u(s.locales, (e) => (l(), i("label", {
					key: e.code,
					class: "flex items-center gap-1.5 text-sm"
				}, [a("input", {
					type: "checkbox",
					checked: c.value.locales.includes(e.code),
					"data-testid": `${s.testid}-locale-${e.code}`,
					onChange: (t) => A(e.code, t)
				}, null, 40, x), a("span", S, d(e.code), 1)]))), 128)), s.locales.length === 0 ? (l(), i("span", C, d(f(k)("showroom.structure.channels.no_locales")), 1)) : r("", !0)]),
				s.action.error("locales") ? (l(), i("small", w, d(s.action.error("locales")), 1)) : r("", !0)
			]), a("div", T, [o(M, {
				label: f(k)("showroom.structure.channels.currencies"),
				hint: f(k)("showroom.structure.channels.currencies_hint"),
				error: s.action.error("currencies")
			}, {
				default: h(() => [o(j, {
					modelValue: c.value.currencies,
					"onUpdate:modelValue": _[0] ||= (e) => c.value.currencies = e,
					class: "w-56",
					"data-testid": `${s.testid}-currencies`
				}, null, 8, ["modelValue", "data-testid"])]),
				_: 1
			}, 8, [
				"label",
				"hint",
				"error"
			]), o(M, {
				label: f(k)("showroom.structure.channels.category_tree"),
				error: s.action.error("category_tree")
			}, {
				default: h(() => [g(a("select", {
					"onUpdate:modelValue": _[1] ||= (e) => c.value.category_tree = e,
					class: "w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
					"data-testid": `${s.testid}-tree`
				}, [a("option", D, d(f(k)("showroom.catalog.none")), 1), (l(!0), i(n, null, u(s.trees, (e) => (l(), i("option", {
					key: e.code,
					value: e.code
				}, d(e.code), 9, O))), 128))], 8, E), [[m, c.value.category_tree]])]),
				_: 1
			}, 8, ["label", "error"])])]);
		};
	}
});
export { k as t };
