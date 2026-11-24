import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { A as t, C as n, D as r, O as i, f as a, j as o, r as s } from "./catalog-DCoO3A8a.js";
import { t as c } from "./AttributeFlags-DO53IOaY.js";
import { _ as l, g as u, n as d, y as f } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as p, AtriumPageHeader as m, navigateTo as h } from "@refactor-circus/atrium";
import { UButton as g, UCard as _, UFormField as v, UInput as y, USelect as b, UTextarea as x } from "@refactor-circus/atrium/ui";
import { computed as S, createElementBlock as C, createElementVNode as w, createTextVNode as T, createVNode as E, defineComponent as D, isRef as O, openBlock as k, reactive as A, ref as j, toDisplayString as M, unref as N, withCtx as P, withModifiers as F } from "vue";
import { useI18n as I } from "vue-i18n";
// src/pages/attributes/create.vue?vue&type=script&setup=true&lang.ts
var L = {
	class: "space-y-4",
	"data-testid": "showroom-attribute-create"
}, R = /* @__PURE__ */ D({
	__name: "create",
	setup(D) {
		let { t: R, locale: z } = I(), B = o(), V = i(), H = t(async (e) => n(await e.GET("/attribute-groups", { params: { query: { per_page: 100 } } }))), U = A({
			code: "",
			type: "",
			group: "",
			label: "",
			settings: "",
			sort_order: "0"
		}), W = S(() => [{
			label: R("showroom.structure.attributes.choose_type"),
			value: null
		}, ...d.map((e) => ({
			label: e,
			value: e
		}))]), G = S(() => [{
			label: R("showroom.structure.attributes.no_group"),
			value: null
		}, ...H.value.map((e) => ({
			label: a(e.labels, z.value, e.code),
			value: e.code
		}))]), K = j({
			is_unique: !1,
			is_localizable: !1,
			is_scopable: !1
		}), q = j(null);
		async function J() {
			let e = l(U.settings);
			if (q.value = e === null ? R("showroom.structure.attributes.invalid_settings") : null, !B || e === null) return;
			let t = {
				code: U.code.trim(),
				type: U.type ?? "",
				group: U.group || null,
				...u(z.value, U.label),
				...K.value,
				settings: e,
				sort_order: f(U.sort_order) ?? 0
			}, i = {};
			await V.run(async () => {
				i.attribute = n(await B.POST("/attributes", { body: r(t) })).data;
			}) && i.attribute && await h(s(i.attribute.code));
		}
		return (t, n) => {
			let r = m, i = y, a = v, o = b, s = x, l = g, u = _, d = p;
			return k(), C("div", L, [E(r, { title: N(R)("showroom.structure.attributes.new") }, null, 8, ["title"]), E(d, { "screen-key": "showroom.attribute-create" }, {
				default: P(() => [E(u, null, {
					default: P(() => [w("form", {
						class: "flex max-w-xl flex-col gap-4",
						"data-testid": "showroom-attribute-create-form",
						onSubmit: F(J, ["prevent"])
					}, [
						E(e, { action: N(V) }, null, 8, ["action"]),
						E(a, {
							label: N(R)("showroom.catalog.fields.code"),
							hint: N(R)("showroom.structure.code_hint"),
							error: N(V).error("code"),
							required: ""
						}, {
							default: P(() => [E(i, {
								modelValue: N(U).code,
								"onUpdate:modelValue": n[0] ||= (e) => N(U).code = e,
								required: "",
								class: "w-64",
								"data-testid": "showroom-attribute-create-code"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						E(a, {
							label: N(R)("showroom.catalog.fields.type"),
							hint: N(R)("showroom.structure.attributes.type_hint"),
							error: N(V).error("type"),
							required: ""
						}, {
							default: P(() => [E(o, {
								modelValue: N(U).type,
								"onUpdate:modelValue": n[1] ||= (e) => N(U).type = e,
								items: N(W),
								placeholder: N(R)("showroom.structure.attributes.choose_type"),
								required: "",
								class: "w-64",
								"data-testid": "showroom-attribute-create-type"
							}, null, 8, [
								"modelValue",
								"items",
								"placeholder"
							])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						E(a, {
							label: N(R)("showroom.catalog.fields.group"),
							error: N(V).error("group")
						}, {
							default: P(() => [E(o, {
								modelValue: N(U).group,
								"onUpdate:modelValue": n[2] ||= (e) => N(U).group = e,
								items: N(G),
								placeholder: N(R)("showroom.structure.attributes.no_group"),
								class: "w-64",
								"data-testid": "showroom-attribute-create-group"
							}, null, 8, [
								"modelValue",
								"items",
								"placeholder"
							])]),
							_: 1
						}, 8, ["label", "error"]),
						E(a, {
							label: N(R)("showroom.catalog.fields.label_in", { locale: N(z) }),
							error: N(V).error("labels")
						}, {
							default: P(() => [E(i, {
								modelValue: N(U).label,
								"onUpdate:modelValue": n[3] ||= (e) => N(U).label = e,
								class: "w-64",
								"data-testid": "showroom-attribute-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						E(c, {
							modelValue: N(K),
							"onUpdate:modelValue": n[4] ||= (e) => O(K) ? K.value = e : null,
							testid: "showroom-attribute-create-flag"
						}, null, 8, ["modelValue"]),
						E(a, {
							label: N(R)("showroom.structure.attributes.settings"),
							hint: N(R)("showroom.structure.attributes.settings_hint"),
							error: N(q) ?? N(V).error("settings")
						}, {
							default: P(() => [E(s, {
								modelValue: N(U).settings,
								"onUpdate:modelValue": n[5] ||= (e) => N(U).settings = e,
								rows: 3,
								class: "w-full font-mono",
								"data-testid": "showroom-attribute-create-settings"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						E(a, {
							label: N(R)("showroom.catalog.fields.sort_order"),
							error: N(V).error("sort_order")
						}, {
							default: P(() => [E(i, {
								modelValue: N(U).sort_order,
								"onUpdate:modelValue": n[6] ||= (e) => N(U).sort_order = e,
								type: "number",
								min: "0",
								class: "w-32",
								"data-testid": "showroom-attribute-create-sort"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						w("div", null, [E(l, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: N(V).busy.value,
							"data-testid": "showroom-attribute-create-submit"
						}, {
							default: P(() => [T(M(N(R)("showroom.structure.attributes.create")), 1)]),
							_: 1
						}, 8, ["loading"])])
					], 32)]),
					_: 1
				})]),
				_: 1
			})]);
		};
	}
});
export { R as default };
