import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, O as a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { t as c } from "./ChannelFields-D7ZaKf1p.js";
import { c as l, h as u, s as d, u as f } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as p, AtriumPageHeader as m, navigateTo as h } from "@refactor-circus/atrium";
import { UButton as ee, UCard as te, UFormField as ne, UInput as g } from "@refactor-circus/atrium/ui";
import { computed as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, isRef as w, openBlock as T, ref as E, toDisplayString as D, unref as O, watch as k, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N } from "vue-router";
// src/pages/channels/[channel].vue?vue&type=script&setup=true&lang.ts
var P = {
	class: "space-y-4",
	"data-testid": "showroom-channel"
}, F = {
	key: 0,
	class: "space-y-5"
}, I = { class: "font-semibold" }, L = { class: "text-sm text-muted" }, R = /* @__PURE__ */ C({
	__name: "[channel]",
	setup(C) {
		let { t: R, locale: z } = M(), B = N(), V = a(), H = _(() => String(B.params.channel)), { client: U, data: W, pending: G, failed: K, status: q, reload: J } = s(async (e) => r(await e.GET("/channels/{channel}", { params: { path: { channel: H.value } } })).data, H), Y = n(async (e) => r(await e.GET("/locales", { params: { query: { per_page: 100 } } }))), X = n(u), Z = _(() => W.value), Q = E(""), $ = E(l());
		k(W, () => {
			Z.value && (Q.value = Z.value.labels[z.value] ?? "", $.value = l(Z.value));
		}, { immediate: !0 });
		async function re() {
			let e = Z.value;
			if (!U || !e) return;
			let t = {
				labels: f(e.labels, z.value, Q.value),
				...d($.value)
			};
			await V.run(async () => r(await U.PATCH("/channels/{channel}", {
				params: { path: { channel: H.value } },
				body: i(t)
			}))) && await J();
		}
		async function ie() {
			U && window.confirm(R("showroom.structure.channels.confirm_delete")) && await V.run(async () => r(await U.DELETE("/channels/{channel}", { params: { path: { channel: H.value } } }))) && await h("/showroom/channels");
		}
		return (n, r) => {
			let i = ee, a = m, s = g, l = ne, u = te, d = p;
			return T(), y("div", P, [S(d, {
				"screen-key": "showroom.channel",
				record: O(H)
			}, {
				default: A(() => [S(t, {
					ready: !O(G) && O(Z) !== null,
					failed: O(K),
					status: O(q)
				}, {
					default: A(() => [O(Z) ? (T(), y("div", F, [
						S(a, {
							title: O(o)(O(Z).labels, O(z), O(Z).code),
							description: O(Z).code,
							"data-testid": "showroom-channel-title"
						}, {
							default: A(() => [S(i, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: O(V).busy.value,
								"data-testid": "showroom-channel-delete",
								onClick: ie
							}, {
								default: A(() => [x(D(O(R)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["title", "description"]),
						S(e, { action: O(V) }, null, 8, ["action"]),
						S(u, null, {
							header: A(() => [b("h2", I, D(O(R)("showroom.structure.details")), 1)]),
							default: A(() => [b("form", {
								class: "flex flex-col gap-4",
								"data-testid": "showroom-channel-form",
								onSubmit: j(re, ["prevent"])
							}, [
								S(l, {
									label: O(R)("showroom.catalog.fields.label_in", { locale: O(z) }),
									error: O(V).error("labels")
								}, {
									default: A(() => [S(s, {
										modelValue: O(Q),
										"onUpdate:modelValue": r[0] ||= (e) => w(Q) ? Q.value = e : null,
										class: "w-64",
										"data-testid": "showroom-channel-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								S(c, {
									modelValue: O($),
									"onUpdate:modelValue": r[1] ||= (e) => w($) ? $.value = e : null,
									locales: O(Y),
									trees: O(X),
									action: O(V),
									testid: "showroom-channel"
								}, null, 8, [
									"modelValue",
									"locales",
									"trees",
									"action"
								]),
								b("div", null, [S(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: O(V).busy.value,
									"data-testid": "showroom-channel-save"
								}, {
									default: A(() => [x(D(O(R)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						}),
						b("p", L, D(O(R)("showroom.structure.channels.delete_note")), 1)
					])) : v("", !0)]),
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
export { R as default };
