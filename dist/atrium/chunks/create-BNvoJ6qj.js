import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { A as t, C as n, O as r, f as i, j as a, y as o } from "./catalog-DCoO3A8a.js";
import { AtriumIntegrationScreen as s, AtriumPageHeader as c, navigateTo as l } from "@refactor-circus/atrium";
import { UButton as u, UCard as d, UFormField as f, UInput as p, USelect as m } from "@refactor-circus/atrium/ui";
import { computed as h, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as b, openBlock as x, reactive as S, toDisplayString as C, unref as w, withCtx as T, withModifiers as E } from "vue";
import { useI18n as D } from "vue-i18n";
// src/pages/products/create.vue?vue&type=script&setup=true&lang.ts
var O = {
	class: "space-y-4",
	"data-testid": "showroom-product-create"
}, k = /* @__PURE__ */ b({
	__name: "create",
	setup(b) {
		let { t: k, locale: A } = D(), j = a(), M = r(), N = t(async (e) => n(await e.GET("/families", { params: { query: { per_page: 100 } } }))), P = S({
			identifier: "",
			family: null
		}), F = h(() => [{
			label: k("showroom.catalog.products.no_family"),
			value: null
		}, ...N.value.map((e) => ({
			label: i(e.labels, A.value, e.code),
			value: e.code
		}))]);
		async function I() {
			if (!j) return;
			let e = { product: null };
			await M.run(async () => {
				e.product = n(await j.POST("/products", { body: {
					identifier: P.identifier.trim(),
					...P.family ? { family: P.family } : {}
				} })).data;
			}) && e.product && await l(o(e.product.id));
		}
		return (t, n) => {
			let r = c, i = p, a = f, o = m, l = u, h = d, b = s;
			return x(), g("div", O, [y(r, { title: w(k)("showroom.catalog.products.new") }, null, 8, ["title"]), y(b, { "screen-key": "showroom.product-create" }, {
				default: T(() => [y(h, null, {
					default: T(() => [_("form", {
						class: "flex max-w-xl flex-col gap-4",
						"data-testid": "showroom-product-create-form",
						onSubmit: E(I, ["prevent"])
					}, [
						y(e, { action: w(M) }, null, 8, ["action"]),
						y(a, {
							label: w(k)("showroom.catalog.fields.identifier"),
							hint: w(k)("showroom.catalog.products.identifier_hint"),
							error: w(M).error("identifier"),
							required: ""
						}, {
							default: T(() => [y(i, {
								modelValue: w(P).identifier,
								"onUpdate:modelValue": n[0] ||= (e) => w(P).identifier = e,
								required: "",
								"data-testid": "showroom-product-create-identifier"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						y(a, {
							label: w(k)("showroom.catalog.fields.family"),
							error: w(M).error("family")
						}, {
							default: T(() => [y(o, {
								modelValue: w(P).family,
								"onUpdate:modelValue": n[1] ||= (e) => w(P).family = e,
								items: w(F),
								placeholder: w(k)("showroom.catalog.products.no_family"),
								class: "w-full",
								"data-testid": "showroom-product-create-family"
							}, null, 8, [
								"modelValue",
								"items",
								"placeholder"
							])]),
							_: 1
						}, 8, ["label", "error"]),
						_("div", null, [y(l, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: w(M).busy.value,
							"data-testid": "showroom-product-create-submit"
						}, {
							default: T(() => [v(C(w(k)("showroom.catalog.products.create")), 1)]),
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
export { k as default };
