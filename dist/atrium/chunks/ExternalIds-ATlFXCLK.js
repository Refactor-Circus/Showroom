import { d as e } from "./catalog-DCoO3A8a.js";
import { UFormField as t, UInput as n } from "@refactor-circus/atrium/ui";
import { Fragment as r, createCommentVNode as i, createElementBlock as a, createElementVNode as o, createVNode as s, defineComponent as c, mergeModels as l, openBlock as u, renderList as d, toDisplayString as f, unref as p, useModel as m, withCtx as h } from "vue";
import { useI18n as g } from "vue-i18n";
// src/components/catalog/ExternalIds.vue?vue&type=script&setup=true&lang.ts
var _ = { "data-testid": "showroom-external-ids" }, v = { class: "mb-1 text-xs font-medium uppercase text-muted" }, y = { class: "flex flex-col gap-3" }, b = ["data-external-source"], x = { class: "w-44 font-mono text-xs" }, S = ["data-testid"], C = {
	key: 0,
	class: "text-xs text-error"
}, w = { class: "flex flex-wrap items-start gap-3" }, T = {
	key: 0,
	class: "text-xs text-error"
}, E = /* @__PURE__ */ c({
	__name: "ExternalIds",
	props: /*@__PURE__*/ l({
		syncedAt: {},
		disabled: { type: Boolean },
		error: { type: Function }
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(c) {
		let l = c, E = m(c, "modelValue"), { t: D } = g();
		return (c, m) => {
			let g = n, O = t;
			return u(), a("div", _, [o("p", v, f(p(D)("showroom.catalog.external_ids.title")), 1), o("div", y, [
				(u(!0), a(r, null, d(Object.keys(E.value.ids), (t) => (u(), a("div", {
					key: t,
					class: "flex flex-wrap items-center gap-3",
					"data-external-source": t
				}, [
					o("span", x, f(t), 1),
					s(g, {
						modelValue: E.value.ids[t],
						"onUpdate:modelValue": (e) => E.value.ids[t] = e,
						"aria-label": t,
						disabled: l.disabled,
						class: "w-56",
						"data-testid": `showroom-external-id-${t}`
					}, null, 8, [
						"modelValue",
						"onUpdate:modelValue",
						"aria-label",
						"disabled",
						"data-testid"
					]),
					o("span", {
						class: "text-xs text-muted",
						"data-testid": `showroom-external-synced-${t}`
					}, f(l.syncedAt[t] ? p(D)("showroom.catalog.external_ids.synced", { time: p(e)(l.syncedAt[t]) }) : p(D)("showroom.catalog.external_ids.never_synced")), 9, S),
					l.error(`external_ids.${t}`, !0) ? (u(), a("small", C, f(l.error(`external_ids.${t}`, !0)), 1)) : i("", !0)
				], 8, b))), 128)),
				o("div", w, [s(O, {
					label: p(D)("showroom.catalog.external_ids.source"),
					hint: p(D)("showroom.catalog.external_ids.source_hint"),
					error: E.value.source ? l.error(`external_ids.${E.value.source}`) : void 0
				}, {
					default: h(() => [s(g, {
						modelValue: E.value.source,
						"onUpdate:modelValue": m[0] ||= (e) => E.value.source = e,
						disabled: l.disabled,
						class: "w-44",
						"data-testid": "showroom-external-new-source"
					}, null, 8, ["modelValue", "disabled"])]),
					_: 1
				}, 8, [
					"label",
					"hint",
					"error"
				]), s(O, { label: p(D)("showroom.catalog.external_ids.id") }, {
					default: h(() => [s(g, {
						modelValue: E.value.id,
						"onUpdate:modelValue": m[1] ||= (e) => E.value.id = e,
						disabled: l.disabled,
						class: "w-56",
						"data-testid": "showroom-external-new-id"
					}, null, 8, ["modelValue", "disabled"])]),
					_: 1
				}, 8, ["label"])]),
				l.error("external_ids", !0) ? (u(), a("small", T, f(l.error("external_ids", !0)), 1)) : i("", !0)
			])]);
		};
	}
});
export { E as t };
