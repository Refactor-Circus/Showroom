import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, O as a, f as ee, k as o } from "./catalog-DCoO3A8a.js";
import { u as s } from "./structure-DbqcM-iO.js";
import { t as c } from "./OwnerTypeRules-DC0UsmJV.js";
import { a as l, i as u } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as d, navigateTo as f } from "@refactor-circus/atrium";
import { UButton as te, UCard as p, UFormField as m, UInput as h } from "@refactor-circus/atrium/ui";
import { computed as g, createCommentVNode as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, isRef as C, openBlock as w, ref as T, toDisplayString as E, unref as D, watch as ne, withCtx as O, withModifiers as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRoute as j } from "vue-router";
// src/pages/owner-types/[ownerType].vue?vue&type=script&setup=true&lang.ts
var M = {
	class: "space-y-4",
	"data-testid": "showroom-owner-type"
}, N = {
	key: 0,
	class: "space-y-5"
}, P = { class: "flex flex-wrap items-start justify-between gap-3" }, F = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-owner-type-title"
}, I = { class: "font-mono text-sm text-muted" }, L = { class: "flex flex-wrap items-center gap-2" }, R = { class: "font-semibold" }, z = /* @__PURE__ */ S({
	__name: "[ownerType]",
	setup(S) {
		let { t: z, locale: B } = A(), V = j(), H = a(), U = g(() => String(V.params.ownerType)), { client: W, data: G, pending: K, failed: q, status: J, reload: Y } = o(async (e) => r(await e.GET("/owner-types/{ownerType}", { params: { path: { ownerType: U.value } } })).data, U), X = g(() => G.value), Z = n(async (e) => r(await e.GET("/owner-types", { params: { query: { per_page: 100 } } }))), Q = T(""), $ = T(u());
		ne(G, () => {
			Q.value = X.value?.labels[B.value] ?? "", $.value = u(X.value);
		}, { immediate: !0 });
		async function re() {
			let e = X.value;
			if (!W || !e) return;
			let t = Z.value.map((e) => e.code), n = {
				labels: s(e.labels, B.value, Q.value),
				...l($.value, [...t, ...$.value.parent_types.filter((e) => !t.includes(e))])
			};
			await H.run(async () => r(await W.PATCH("/owner-types/{ownerType}", {
				params: { path: { ownerType: U.value } },
				body: i(n)
			}))) && await Y();
		}
		async function ie() {
			W && window.confirm(z("showroom.owner_types.confirm_delete")) && await H.run(async () => r(await W.DELETE("/owner-types/{ownerType}", { params: { path: { ownerType: U.value } } }))) && await f("/showroom/owner-types");
		}
		return (n, r) => {
			let i = te, a = h, o = m, s = p, l = d;
			return w(), v("div", M, [x(l, {
				"screen-key": "showroom.owner-type",
				record: D(U)
			}, {
				default: O(() => [x(t, {
					ready: !D(K) && D(X) !== null,
					failed: D(q),
					status: D(J)
				}, {
					default: O(() => [D(X) ? (w(), v("div", N, [
						y("div", P, [y("div", null, [y("h1", F, E(D(ee)(D(X).labels, D(B), D(X).code)), 1), y("p", I, E(D(X).code), 1)]), y("div", L, [x(i, {
							to: `/showroom/owners?type=${encodeURIComponent(D(X).code)}`,
							variant: "outline",
							icon: "i-lucide-building-2",
							"data-testid": "showroom-owner-type-owners"
						}, {
							default: O(() => [b(E(D(z)("showroom.owner_types.owners_of_type", { count: D(X).owners_count ?? 0 })), 1)]),
							_: 1
						}, 8, ["to"]), x(i, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: D(H).busy.value,
							"data-testid": "showroom-owner-type-delete",
							onClick: ie
						}, {
							default: O(() => [b(E(D(z)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])]),
						x(e, { action: D(H) }, null, 8, ["action"]),
						x(s, null, {
							header: O(() => [y("h2", R, E(D(z)("showroom.owner_types.rules")), 1)]),
							default: O(() => [y("form", {
								class: "flex flex-col gap-4",
								"data-testid": "showroom-owner-type-form",
								onSubmit: k(re, ["prevent"])
							}, [
								x(o, {
									label: D(z)("showroom.catalog.fields.label_in", { locale: D(B) }),
									error: D(H).error("labels")
								}, {
									default: O(() => [x(a, {
										modelValue: D(Q),
										"onUpdate:modelValue": r[0] ||= (e) => C(Q) ? Q.value = e : null,
										class: "w-64",
										"data-testid": "showroom-owner-type-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								x(c, {
									modelValue: D($),
									"onUpdate:modelValue": r[1] ||= (e) => C($) ? $.value = e : null,
									types: D(Z),
									action: D(H),
									testid: "showroom-owner-type"
								}, null, 8, [
									"modelValue",
									"types",
									"action"
								]),
								y("div", null, [x(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: D(H).busy.value,
									"data-testid": "showroom-owner-type-save"
								}, {
									default: O(() => [b(E(D(z)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						})
					])) : _("", !0)]),
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
