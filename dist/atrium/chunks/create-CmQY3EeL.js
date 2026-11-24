import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { A as t, C as n, D as r, O as i, f as a, j as o, r as s } from "./catalog-DCoO3A8a.js";
import { t as c } from "./AttributeFlags-B7-82bx1.js";
import { _ as l, g as u, n as d, y as f } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as p, navigateTo as m } from "@refactor-circus/atrium";
import { UButton as h, UCard as g, UFormField as _, UInput as v, UTextarea as y } from "@refactor-circus/atrium/ui";
import { Fragment as b, createElementBlock as x, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, isRef as E, openBlock as D, reactive as O, ref as k, renderList as A, toDisplayString as j, unref as M, vModelSelect as N, withCtx as P, withDirectives as F, withModifiers as I } from "vue";
import { useI18n as L } from "vue-i18n";
// src/pages/attributes/create.vue?vue&type=script&setup=true&lang.ts
var R = {
	class: "space-y-4",
	"data-testid": "showroom-attribute-create"
}, z = { class: "text-xl font-semibold" }, B = { value: "" }, V = ["value"], H = { value: "" }, U = ["value"], W = /* @__PURE__ */ T({
	__name: "create",
	setup(T) {
		let { t: W, locale: G } = L(), K = o(), q = i(), J = t(async (e) => n(await e.GET("/attribute-groups", { params: { query: { per_page: 100 } } }))), Y = O({
			code: "",
			type: "",
			group: "",
			label: "",
			settings: "",
			sort_order: "0"
		}), X = k({
			is_unique: !1,
			is_localizable: !1,
			is_scopable: !1
		}), Z = k(null);
		async function Q() {
			let e = l(Y.settings);
			if (Z.value = e === null ? W("showroom.structure.attributes.invalid_settings") : null, !K || e === null) return;
			let t = {
				code: Y.code.trim(),
				type: Y.type,
				group: Y.group || null,
				...u(G.value, Y.label),
				...X.value,
				settings: e,
				sort_order: f(Y.sort_order) ?? 0
			}, i = {};
			await q.run(async () => {
				i.attribute = n(await K.POST("/attributes", { body: r(t) })).data;
			}) && i.attribute && await m(s(i.attribute.code));
		}
		return (t, n) => {
			let r = v, i = _, o = y, s = h, l = g, u = p;
			return D(), x("div", R, [S("h1", z, j(M(W)("showroom.structure.attributes.new")), 1), w(u, { "screen-key": "showroom.attribute-create" }, {
				default: P(() => [w(l, null, {
					default: P(() => [S("form", {
						class: "flex max-w-xl flex-col gap-4",
						"data-testid": "showroom-attribute-create-form",
						onSubmit: I(Q, ["prevent"])
					}, [
						w(e, { action: M(q) }, null, 8, ["action"]),
						w(i, {
							label: M(W)("showroom.catalog.fields.code"),
							hint: M(W)("showroom.structure.code_hint"),
							error: M(q).error("code"),
							required: ""
						}, {
							default: P(() => [w(r, {
								modelValue: M(Y).code,
								"onUpdate:modelValue": n[0] ||= (e) => M(Y).code = e,
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
						w(i, {
							label: M(W)("showroom.catalog.fields.type"),
							hint: M(W)("showroom.structure.attributes.type_hint"),
							error: M(q).error("type"),
							required: ""
						}, {
							default: P(() => [F(S("select", {
								"onUpdate:modelValue": n[1] ||= (e) => M(Y).type = e,
								required: "",
								class: "w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-attribute-create-type"
							}, [S("option", B, j(M(W)("showroom.structure.attributes.choose_type")), 1), (D(!0), x(b, null, A(M(d), (e) => (D(), x("option", {
								key: e,
								value: e
							}, j(e), 9, V))), 128))], 512), [[N, M(Y).type]])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						w(i, {
							label: M(W)("showroom.catalog.fields.group"),
							error: M(q).error("group")
						}, {
							default: P(() => [F(S("select", {
								"onUpdate:modelValue": n[2] ||= (e) => M(Y).group = e,
								class: "w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-attribute-create-group"
							}, [S("option", H, j(M(W)("showroom.structure.attributes.no_group")), 1), (D(!0), x(b, null, A(M(J), (e) => (D(), x("option", {
								key: e.code,
								value: e.code
							}, j(M(a)(e.labels, M(G), e.code)), 9, U))), 128))], 512), [[N, M(Y).group]])]),
							_: 1
						}, 8, ["label", "error"]),
						w(i, {
							label: M(W)("showroom.catalog.fields.label_in", { locale: M(G) }),
							error: M(q).error("labels")
						}, {
							default: P(() => [w(r, {
								modelValue: M(Y).label,
								"onUpdate:modelValue": n[3] ||= (e) => M(Y).label = e,
								class: "w-64",
								"data-testid": "showroom-attribute-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						w(c, {
							modelValue: M(X),
							"onUpdate:modelValue": n[4] ||= (e) => E(X) ? X.value = e : null,
							testid: "showroom-attribute-create-flag"
						}, null, 8, ["modelValue"]),
						w(i, {
							label: M(W)("showroom.structure.attributes.settings"),
							hint: M(W)("showroom.structure.attributes.settings_hint"),
							error: M(Z) ?? M(q).error("settings")
						}, {
							default: P(() => [w(o, {
								modelValue: M(Y).settings,
								"onUpdate:modelValue": n[5] ||= (e) => M(Y).settings = e,
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
						w(i, {
							label: M(W)("showroom.catalog.fields.sort_order"),
							error: M(q).error("sort_order")
						}, {
							default: P(() => [w(r, {
								modelValue: M(Y).sort_order,
								"onUpdate:modelValue": n[6] ||= (e) => M(Y).sort_order = e,
								type: "number",
								min: "0",
								class: "w-32",
								"data-testid": "showroom-attribute-create-sort"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						S("div", null, [w(s, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: M(q).busy.value,
							"data-testid": "showroom-attribute-create-submit"
						}, {
							default: P(() => [C(j(M(W)("showroom.structure.attributes.create")), 1)]),
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
export { W as default };
