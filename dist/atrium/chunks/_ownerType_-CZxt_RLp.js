import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, O as a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { u as c } from "./structure-DbqcM-iO.js";
import { t as l } from "./OwnerTypeRules-CvmgahMf.js";
import { a as u, i as d } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as f, AtriumPageHeader as p, navigateTo as m } from "@refactor-circus/atrium";
import { UButton as h, UCard as g, UFormField as _, UInput as ee } from "@refactor-circus/atrium/ui";
import { computed as v, createCommentVNode as y, createElementBlock as b, createElementVNode as x, createTextVNode as S, createVNode as C, defineComponent as w, isRef as T, openBlock as E, ref as D, toDisplayString as O, unref as k, watch as te, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N } from "vue-router";
// src/pages/owner-types/[ownerType].vue?vue&type=script&setup=true&lang.ts
var P = {
	class: "space-y-4",
	"data-testid": "showroom-owner-type"
}, F = {
	key: 0,
	class: "space-y-5"
}, I = {
	class: "truncate",
	"data-testid": "showroom-owner-type-title"
}, L = { class: "font-mono text-sm" }, R = { class: "font-semibold" }, z = /* @__PURE__ */ w({
	__name: "[ownerType]",
	setup(w) {
		let { t: z, locale: B } = M(), V = N(), H = a(), U = v(() => String(V.params.ownerType)), { client: W, data: G, pending: K, failed: q, status: J, reload: Y } = s(async (e) => r(await e.GET("/owner-types/{ownerType}", { params: { path: { ownerType: U.value } } })).data, U), X = v(() => G.value), Z = n(async (e) => r(await e.GET("/owner-types", { params: { query: { per_page: 100 } } }))), Q = D(""), $ = D(d());
		te(G, () => {
			Q.value = X.value?.labels[B.value] ?? "", $.value = d(X.value);
		}, { immediate: !0 });
		async function ne() {
			let e = X.value;
			if (!W || !e) return;
			let t = Z.value.map((e) => e.code), n = {
				labels: c(e.labels, B.value, Q.value),
				...u($.value, [...t, ...$.value.parent_types.filter((e) => !t.includes(e))])
			};
			await H.run(async () => r(await W.PATCH("/owner-types/{ownerType}", {
				params: { path: { ownerType: U.value } },
				body: i(n)
			}))) && await Y();
		}
		async function re() {
			W && window.confirm(z("showroom.owner_types.confirm_delete")) && await H.run(async () => r(await W.DELETE("/owner-types/{ownerType}", { params: { path: { ownerType: U.value } } }))) && await m("/showroom/owner-types");
		}
		return (n, r) => {
			let i = h, a = p, s = ee, c = _, u = g, d = f;
			return E(), b("div", P, [C(d, {
				"screen-key": "showroom.owner-type",
				record: k(U)
			}, {
				default: A(() => [C(t, {
					ready: !k(K) && k(X) !== null,
					failed: k(q),
					status: k(J)
				}, {
					default: A(() => [k(X) ? (E(), b("div", F, [
						C(a, { overline: k(z)("showroom.owner_types.title") }, {
							title: A(() => [x("span", I, O(k(o)(k(X).labels, k(B), k(X).code)), 1)]),
							description: A(() => [x("span", L, O(k(X).code), 1)]),
							default: A(() => [C(i, {
								to: `/showroom/owners?type=${encodeURIComponent(k(X).code)}`,
								variant: "outline",
								icon: "i-lucide-building-2",
								"data-testid": "showroom-owner-type-owners"
							}, {
								default: A(() => [S(O(k(z)("showroom.owner_types.owners_of_type", { count: k(X).owners_count ?? 0 })), 1)]),
								_: 1
							}, 8, ["to"]), C(i, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: k(H).busy.value,
								"data-testid": "showroom-owner-type-delete",
								onClick: re
							}, {
								default: A(() => [S(O(k(z)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["overline"]),
						C(e, { action: k(H) }, null, 8, ["action"]),
						C(u, null, {
							header: A(() => [x("h2", R, O(k(z)("showroom.owner_types.rules")), 1)]),
							default: A(() => [x("form", {
								class: "flex flex-col gap-4",
								"data-testid": "showroom-owner-type-form",
								onSubmit: j(ne, ["prevent"])
							}, [
								C(c, {
									label: k(z)("showroom.catalog.fields.label_in", { locale: k(B) }),
									error: k(H).error("labels")
								}, {
									default: A(() => [C(s, {
										modelValue: k(Q),
										"onUpdate:modelValue": r[0] ||= (e) => T(Q) ? Q.value = e : null,
										class: "w-64",
										"data-testid": "showroom-owner-type-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								C(l, {
									modelValue: k($),
									"onUpdate:modelValue": r[1] ||= (e) => T($) ? $.value = e : null,
									types: k(Z),
									action: k(H),
									testid: "showroom-owner-type"
								}, null, 8, [
									"modelValue",
									"types",
									"action"
								]),
								x("div", null, [C(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: k(H).busy.value,
									"data-testid": "showroom-owner-type-save"
								}, {
									default: A(() => [S(O(k(z)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						})
					])) : y("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { z as default };
