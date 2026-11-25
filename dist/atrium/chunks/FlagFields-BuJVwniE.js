import { r as e } from "./structure-DbqcM-iO.js";
import { UCheckbox as t, UFormField as n, UInput as r } from "@refactor-circus/atrium/ui";
import { Fragment as i, createBlock as a, createCommentVNode as o, createElementBlock as s, createElementVNode as c, createVNode as l, defineComponent as u, mergeModels as d, openBlock as f, renderList as p, toDisplayString as m, unref as h, useModel as g, withCtx as _ } from "vue";
import { useI18n as v } from "vue-i18n";
// src/components/structure/FlagFields.vue?vue&type=script&setup=true&lang.ts
var y = { class: "flex flex-wrap items-start gap-3" }, b = { class: "mb-1 text-xs font-medium uppercase text-muted" }, x = { class: "flex flex-wrap gap-3" }, S = {
	key: 0,
	class: "text-xs text-error"
}, C = /* @__PURE__ */ u({
	__name: "FlagFields",
	props: /*@__PURE__*/ d({
		action: {},
		testid: {}
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(u) {
		let d = g(u, "modelValue"), { t: C } = v();
		function w(t, n) {
			let r = n === !0;
			d.value.applies_to = r ? e.filter((e) => e === t || d.value.applies_to.includes(e)) : d.value.applies_to.filter((e) => e !== t);
		}
		return (g, v) => {
			let T = t, E = r, D = n;
			return f(), s("div", y, [c("div", null, [
				c("p", b, m(h(C)("showroom.structure.flags.applies_to")), 1),
				c("div", x, [(f(!0), s(i, null, p(h(e), (e) => (f(), a(T, {
					key: e,
					"model-value": d.value.applies_to.includes(e),
					label: h(C)(`showroom.structure.flags.subject_${e}`),
					"data-testid": `${u.testid}-applies-${e}`,
					"onUpdate:modelValue": (t) => w(e, t)
				}, null, 8, [
					"model-value",
					"label",
					"data-testid",
					"onUpdate:modelValue"
				]))), 128))]),
				u.action.error("applies_to") ? (f(), s("small", S, m(u.action.error("applies_to")), 1)) : o("", !0)
			]), l(D, {
				label: h(C)("showroom.catalog.fields.sort_order"),
				error: u.action.error("sort_order")
			}, {
				default: _(() => [l(E, {
					modelValue: d.value.sort_order,
					"onUpdate:modelValue": v[0] ||= (e) => d.value.sort_order = e,
					type: "number",
					min: "0",
					class: "w-32",
					"data-testid": `${u.testid}-sort`
				}, null, 8, ["modelValue", "data-testid"])]),
				_: 1
			}, 8, ["label", "error"])]);
		};
	}
});
export { C as t };
