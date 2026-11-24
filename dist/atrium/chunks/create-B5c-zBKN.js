import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { A as t, C as n, O as r, f as i, j as a, y as o } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as s, navigateTo as c } from "@refactor-circus/atrium";
import { UButton as l, UCard as u, UFormField as d, UInput as f } from "@refactor-circus/atrium/ui";
import { Fragment as p, createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as v, openBlock as y, reactive as b, renderList as x, toDisplayString as S, unref as C, vModelSelect as w, withCtx as T, withDirectives as E, withModifiers as D } from "vue";
import { useI18n as O } from "vue-i18n";
// src/pages/products/create.vue?vue&type=script&setup=true&lang.ts
var k = {
	class: "space-y-4",
	"data-testid": "showroom-product-create"
}, A = { class: "text-xl font-semibold" }, j = { value: "" }, M = ["value"], N = /* @__PURE__ */ v({
	__name: "create",
	setup(v) {
		let { t: N, locale: P } = O(), F = a(), I = r(), L = t(async (e) => n(await e.GET("/families", { params: { query: { per_page: 100 } } }))), R = b({
			identifier: "",
			family: ""
		});
		async function z() {
			if (!F) return;
			let e = { product: null };
			await I.run(async () => {
				e.product = n(await F.POST("/products", { body: {
					identifier: R.identifier.trim(),
					...R.family ? { family: R.family } : {}
				} })).data;
			}) && e.product && await c(o(e.product.id));
		}
		return (t, n) => {
			let r = f, a = d, o = l, c = u, v = s;
			return y(), m("div", k, [h("h1", A, S(C(N)("showroom.catalog.products.new")), 1), _(v, { "screen-key": "showroom.product-create" }, {
				default: T(() => [_(c, null, {
					default: T(() => [h("form", {
						class: "flex max-w-xl flex-col gap-4",
						"data-testid": "showroom-product-create-form",
						onSubmit: D(z, ["prevent"])
					}, [
						_(e, { action: C(I) }, null, 8, ["action"]),
						_(a, {
							label: C(N)("showroom.catalog.fields.identifier"),
							hint: C(N)("showroom.catalog.products.identifier_hint"),
							error: C(I).error("identifier"),
							required: ""
						}, {
							default: T(() => [_(r, {
								modelValue: C(R).identifier,
								"onUpdate:modelValue": n[0] ||= (e) => C(R).identifier = e,
								required: "",
								"data-testid": "showroom-product-create-identifier"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						_(a, {
							label: C(N)("showroom.catalog.fields.family"),
							error: C(I).error("family")
						}, {
							default: T(() => [E(h("select", {
								"onUpdate:modelValue": n[1] ||= (e) => C(R).family = e,
								class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-product-create-family"
							}, [h("option", j, S(C(N)("showroom.catalog.products.no_family")), 1), (y(!0), m(p, null, x(C(L), (e) => (y(), m("option", {
								key: e.code,
								value: e.code
							}, S(C(i)(e.labels, C(P), e.code)), 9, M))), 128))], 512), [[w, C(R).family]])]),
							_: 1
						}, 8, ["label", "error"]),
						h("div", null, [_(o, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: C(I).busy.value,
							"data-testid": "showroom-product-create-submit"
						}, {
							default: T(() => [g(S(C(N)("showroom.catalog.products.create")), 1)]),
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
export { N as default };
