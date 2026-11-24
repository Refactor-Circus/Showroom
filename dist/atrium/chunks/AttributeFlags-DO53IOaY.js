import { t as e } from "./structure-DbqcM-iO.js";
import { UCheckbox as t } from "@refactor-circus/atrium/ui";
import { Fragment as n, createBlock as r, createElementBlock as i, defineComponent as a, mergeModels as o, openBlock as s, renderList as c, unref as l, useModel as u } from "vue";
import { useI18n as d } from "vue-i18n";
// src/components/structure/AttributeFlags.vue?vue&type=script&setup=true&lang.ts
var f = { class: "flex flex-col gap-2" }, p = /* @__PURE__ */ a({
	__name: "AttributeFlags",
	props: /*@__PURE__*/ o({ testid: {} }, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(a) {
		let o = u(a, "modelValue"), { t: p } = d();
		return (u, d) => {
			let m = t;
			return s(), i("div", f, [(s(!0), i(n, null, c(l(e), (e) => (s(), r(m, {
				key: e,
				modelValue: o.value[e],
				"onUpdate:modelValue": (t) => o.value[e] = t,
				label: l(p)(`showroom.structure.attributes.${e}`),
				"data-testid": `${a.testid}-${e}`
			}, null, 8, [
				"modelValue",
				"onUpdate:modelValue",
				"label",
				"data-testid"
			]))), 128))]);
		};
	}
});
export { p as t };
