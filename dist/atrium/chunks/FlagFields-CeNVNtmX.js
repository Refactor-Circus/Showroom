import { r as e } from "./structure-DbqcM-iO.js";
import { UFormField as t, UInput as n } from "@refactor-circus/atrium/ui";
import { Fragment as r, createCommentVNode as i, createElementBlock as a, createElementVNode as o, createTextVNode as s, createVNode as c, defineComponent as l, mergeModels as u, openBlock as d, renderList as f, toDisplayString as p, unref as m, useModel as h, withCtx as g } from "vue";
import { useI18n as _ } from "vue-i18n";
// src/components/structure/FlagFields.vue?vue&type=script&setup=true&lang.ts
var v = { class: "flex flex-wrap items-start gap-3" }, y = { class: "mb-1 text-xs font-medium uppercase text-muted" }, b = { class: "flex flex-wrap gap-3" }, x = [
	"checked",
	"data-testid",
	"onChange"
], S = {
	key: 0,
	class: "text-xs text-error"
}, C = /* @__PURE__ */ l({
	__name: "FlagFields",
	props: /*@__PURE__*/ u({
		action: {},
		testid: {}
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(l) {
		let u = h(l, "modelValue"), { t: C } = _();
		function w(t, n) {
			let r = n.target.checked;
			u.value.applies_to = r ? e.filter((e) => e === t || u.value.applies_to.includes(e)) : u.value.applies_to.filter((e) => e !== t);
		}
		return (h, _) => {
			let T = n, E = t;
			return d(), a("div", v, [o("div", null, [
				o("p", y, p(m(C)("showroom.structure.flags.applies_to")), 1),
				o("div", b, [(d(!0), a(r, null, f(m(e), (e) => (d(), a("label", {
					key: e,
					class: "flex items-center gap-1.5 text-sm"
				}, [o("input", {
					type: "checkbox",
					checked: u.value.applies_to.includes(e),
					"data-testid": `${l.testid}-applies-${e}`,
					onChange: (t) => w(e, t)
				}, null, 40, x), s(" " + p(m(C)(`showroom.structure.flags.subject_${e}`)), 1)]))), 128))]),
				l.action.error("applies_to") ? (d(), a("small", S, p(l.action.error("applies_to")), 1)) : i("", !0)
			]), c(E, {
				label: m(C)("showroom.catalog.fields.sort_order"),
				error: l.action.error("sort_order")
			}, {
				default: g(() => [c(T, {
					modelValue: u.value.sort_order,
					"onUpdate:modelValue": _[0] ||= (e) => u.value.sort_order = e,
					type: "number",
					min: "0",
					class: "w-32",
					"data-testid": `${l.testid}-sort`
				}, null, 8, ["modelValue", "data-testid"])]),
				_: 1
			}, 8, ["label", "error"])]);
		};
	}
});
export { C as t };
