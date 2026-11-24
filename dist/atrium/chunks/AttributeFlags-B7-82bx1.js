import { t as e } from "./structure-DbqcM-iO.js";
import { Fragment as t, createElementBlock as n, createElementVNode as r, createTextVNode as i, defineComponent as a, mergeModels as o, openBlock as s, renderList as c, toDisplayString as l, unref as u, useModel as d, vModelCheckbox as f, withDirectives as p } from "vue";
import { useI18n as m } from "vue-i18n";
// src/components/structure/AttributeFlags.vue?vue&type=script&setup=true&lang.ts
var h = { class: "flex flex-col gap-2" }, g = ["onUpdate:modelValue", "data-testid"], _ = /* @__PURE__ */ a({
	__name: "AttributeFlags",
	props: /*@__PURE__*/ o({ testid: {} }, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(a) {
		let o = d(a, "modelValue"), { t: _ } = m();
		return (d, m) => (s(), n("div", h, [(s(!0), n(t, null, c(u(e), (e) => (s(), n("label", {
			key: e,
			class: "flex items-center gap-2 text-sm"
		}, [p(r("input", {
			"onUpdate:modelValue": (t) => o.value[e] = t,
			type: "checkbox",
			"data-testid": `${a.testid}-${e}`
		}, null, 8, g), [[f, o.value[e]]]), i(" " + l(u(_)(`showroom.structure.attributes.${e}`)), 1)]))), 128))]));
	}
});
export { _ as t };
