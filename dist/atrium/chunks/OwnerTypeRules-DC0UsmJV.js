import { f as e } from "./catalog-DCoO3A8a.js";
import { Fragment as t, createCommentVNode as n, createElementBlock as r, createElementVNode as i, createTextVNode as a, defineComponent as o, mergeModels as s, openBlock as c, renderList as l, toDisplayString as u, unref as d, useModel as f, vModelCheckbox as p, withDirectives as m } from "vue";
import { useI18n as h } from "vue-i18n";
// src/components/owners/OwnerTypeRules.vue?vue&type=script&setup=true&lang.ts
var g = ["data-testid"], _ = { class: "mb-1 text-xs font-medium uppercase text-muted" }, v = { class: "flex items-center gap-2 text-sm" }, y = ["data-testid"], b = {
	key: 0,
	class: "mt-2 flex flex-wrap gap-3"
}, x = [
	"value",
	"checked",
	"data-testid",
	"onChange"
], S = { class: "font-mono text-xs" }, C = {
	key: 0,
	class: "text-muted"
}, w = {
	key: 0,
	class: "text-sm text-muted"
}, T = {
	key: 1,
	class: "text-xs text-error"
}, E = { class: "flex items-center gap-2 text-sm" }, D = ["data-testid"], O = { class: "flex items-center gap-2 text-sm" }, k = ["data-testid"], A = /* @__PURE__ */ o({
	__name: "OwnerTypeRules",
	props: /*@__PURE__*/ s({
		types: {},
		action: {},
		testid: {}
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(o) {
		let s = o, A = f(o, "modelValue"), { t: j, locale: M } = h();
		function N(e, t) {
			A.value.parent_types = t ? [...A.value.parent_types.filter((t) => t !== e), e] : A.value.parent_types.filter((t) => t !== e);
		}
		function P(e) {
			return e.target.checked;
		}
		return (o, f) => (c(), r("div", {
			class: "flex flex-col gap-3",
			"data-testid": `${s.testid}-rules`
		}, [
			i("div", null, [
				i("p", _, u(d(j)("showroom.owner_types.parent_types")), 1),
				i("label", v, [m(i("input", {
					"onUpdate:modelValue": f[0] ||= (e) => A.value.any_parent = e,
					type: "checkbox",
					"data-testid": `${s.testid}-any-parent`
				}, null, 8, y), [[p, A.value.any_parent]]), a(" " + u(d(j)("showroom.owner_types.any_parent")), 1)]),
				A.value.any_parent ? n("", !0) : (c(), r("div", b, [(c(!0), r(t, null, l(s.types, (t) => (c(), r("label", {
					key: t.code,
					class: "flex items-center gap-1.5 text-sm"
				}, [
					i("input", {
						type: "checkbox",
						value: t.code,
						checked: A.value.parent_types.includes(t.code),
						"data-testid": `${s.testid}-parent-${t.code}`,
						onChange: (e) => N(t.code, P(e))
					}, null, 40, x),
					i("span", S, u(t.code), 1),
					d(e)(t.labels, d(M), t.code) === t.code ? n("", !0) : (c(), r("span", C, u(d(e)(t.labels, d(M), t.code)), 1))
				]))), 128)), s.types.length === 0 ? (c(), r("span", w, u(d(j)("showroom.owner_types.no_types")), 1)) : n("", !0)])),
				s.action.error("parent_types") ? (c(), r("small", T, u(s.action.error("parent_types")), 1)) : n("", !0)
			]),
			i("label", E, [m(i("input", {
				"onUpdate:modelValue": f[1] ||= (e) => A.value.can_be_root = e,
				type: "checkbox",
				"data-testid": `${s.testid}-root`
			}, null, 8, D), [[p, A.value.can_be_root]]), a(" " + u(d(j)("showroom.owner_types.can_be_root")), 1)]),
			i("label", O, [m(i("input", {
				"onUpdate:modelValue": f[2] ||= (e) => A.value.owns_products = e,
				type: "checkbox",
				"data-testid": `${s.testid}-owns`
			}, null, 8, k), [[p, A.value.owns_products]]), a(" " + u(d(j)("showroom.owner_types.owns_products")), 1)])
		], 8, g));
	}
});
export { A as t };
