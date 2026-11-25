import { f as e } from "./catalog-DCoO3A8a.js";
import { UCheckbox as t } from "@refactor-circus/atrium/ui";
import { Fragment as n, createBlock as r, createCommentVNode as i, createElementBlock as a, createElementVNode as o, createVNode as s, defineComponent as c, mergeModels as l, openBlock as u, renderList as d, toDisplayString as f, unref as p, useModel as m, withCtx as h } from "vue";
import { useI18n as g } from "vue-i18n";
// src/components/owners/OwnerTypeRules.vue?vue&type=script&setup=true&lang.ts
var _ = ["data-testid"], v = { class: "mb-1 text-xs font-medium uppercase text-muted" }, y = {
	key: 0,
	class: "mt-2 flex flex-wrap gap-3"
}, b = { class: "font-mono text-xs" }, x = {
	key: 0,
	class: "ms-1.5 text-muted"
}, S = {
	key: 0,
	class: "text-sm text-muted"
}, C = {
	key: 1,
	class: "text-xs text-error"
}, w = /* @__PURE__ */ c({
	__name: "OwnerTypeRules",
	props: /*@__PURE__*/ l({
		types: {},
		action: {},
		testid: {}
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(c) {
		let l = c, w = m(c, "modelValue"), { t: T, locale: E } = g();
		function D(e, t) {
			w.value.parent_types = t ? [...w.value.parent_types.filter((t) => t !== e), e] : w.value.parent_types.filter((t) => t !== e);
		}
		return (c, m) => {
			let g = t;
			return u(), a("div", {
				class: "flex flex-col gap-3",
				"data-testid": `${l.testid}-rules`
			}, [
				o("div", null, [
					o("p", v, f(p(T)("showroom.owner_types.parent_types")), 1),
					s(g, {
						modelValue: w.value.any_parent,
						"onUpdate:modelValue": m[0] ||= (e) => w.value.any_parent = e,
						label: p(T)("showroom.owner_types.any_parent"),
						"data-testid": `${l.testid}-any-parent`
					}, null, 8, [
						"modelValue",
						"label",
						"data-testid"
					]),
					w.value.any_parent ? i("", !0) : (u(), a("div", y, [(u(!0), a(n, null, d(l.types, (t) => (u(), r(g, {
						key: t.code,
						"model-value": w.value.parent_types.includes(t.code),
						"data-testid": `${l.testid}-parent-${t.code}`,
						"onUpdate:modelValue": (e) => D(t.code, e === !0)
					}, {
						label: h(() => [o("span", b, f(t.code), 1), p(e)(t.labels, p(E), t.code) === t.code ? i("", !0) : (u(), a("span", x, f(p(e)(t.labels, p(E), t.code)), 1))]),
						_: 2
					}, 1032, [
						"model-value",
						"data-testid",
						"onUpdate:modelValue"
					]))), 128)), l.types.length === 0 ? (u(), a("span", S, f(p(T)("showroom.owner_types.no_types")), 1)) : i("", !0)])),
					l.action.error("parent_types") ? (u(), a("small", C, f(l.action.error("parent_types")), 1)) : i("", !0)
				]),
				s(g, {
					modelValue: w.value.can_be_root,
					"onUpdate:modelValue": m[1] ||= (e) => w.value.can_be_root = e,
					label: p(T)("showroom.owner_types.can_be_root"),
					"data-testid": `${l.testid}-root`
				}, null, 8, [
					"modelValue",
					"label",
					"data-testid"
				]),
				s(g, {
					modelValue: w.value.owns_products,
					"onUpdate:modelValue": m[2] ||= (e) => w.value.owns_products = e,
					label: p(T)("showroom.owner_types.owns_products"),
					"data-testid": `${l.testid}-owns`
				}, null, 8, [
					"modelValue",
					"label",
					"data-testid"
				])
			], 8, _);
		};
	}
});
export { w as t };
